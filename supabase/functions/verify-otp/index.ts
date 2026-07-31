import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { normalizeSomaliPhone, toE164SomaliPhone } from '../_shared/phone.ts'

const corsHeaders = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
}

const MAX_VERIFY_ATTEMPTS = 5

function buildJsonResponse(body: Record<string, unknown>, status = 200) {
    return new Response(JSON.stringify(body), {
        status,
        headers: { ...corsHeaders, 'Content-Type': 'application/json' },
    })
}

function generateSessionPassword() {
    return `session-${crypto.randomUUID()}-${crypto.randomUUID()}`
}

function optionalDeviceText(value: unknown, maxLength: number) {
    if (typeof value !== 'string') return null
    const cleanValue = value.trim()
    if (!cleanValue) return null
    return cleanValue.slice(0, maxLength)
}

function verificationError(status: string, attemptsRemaining = 0) {
    const messages: Record<string, string> = {
        parent_not_allowed: 'Lambarkan school-kan kama diiwaangashana. La xidhiidh maamulka.',
        missing_otp: 'OTP lama helin. Fadlan mar kale codso.',
        expired: 'OTP-ga waqtigiisii wuu dhacay. Fadlan mar kale codso.',
        invalid_code: `OTP-ga aad gelisay sax ma aha. Waxaad haysataa ${attemptsRemaining} isku day.`,
        max_attempts: 'Isku dayadii OTP-ga way dhammaadeen. Fadlan OTP cusub codso.',
        already_consumed: 'OTP-kan hore ayaa loo isticmaalay. Fadlan OTP cusub codso.',
    }

    return messages[status] || 'OTP verification-ku ma guulaysan.'
}

Deno.serve(async (req) => {
    if (req.method === 'OPTIONS') {
        return new Response('ok', { headers: corsHeaders })
    }

    const supabaseUrl = Deno.env.get('SUPABASE_URL') ?? ''
    const serviceRoleKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    const anonKey = Deno.env.get('SUPABASE_ANON_KEY') ?? ''

    const adminClient = createClient(supabaseUrl, serviceRoleKey, {
        auth: { persistSession: false, autoRefreshToken: false },
    })
    const authClient = createClient(supabaseUrl, anonKey, {
        auth: { persistSession: false, autoRefreshToken: false },
    })

    try {
        const {
            phone,
            code,
            school_id,
            device_id,
            device_name,
            platform,
            app_variant,
        } = await req.json()
        const schoolId = Number(school_id)
        const cleanDeviceId = typeof device_id === 'string' ? device_id.trim() : ''

        if (
            !phone ||
            !code ||
            !Number.isSafeInteger(schoolId) ||
            schoolId <= 0 ||
            !/^[A-Za-z0-9._:-]{8,160}$/.test(cleanDeviceId)
        ) {
            throw new Error('Phone number, OTP code, school_id, and a valid device_id are required.')
        }

        const cleanPhone = normalizeSomaliPhone(phone)
        const authPhone = toE164SomaliPhone(phone)
        const cleanCode = String(code).trim()

        if (!cleanPhone || !authPhone || !/^\d{6}$/.test(cleanCode)) {
            throw new Error('Phone number ama OTP code sax ma aha.')
        }

        const { data: consumedOtp, error: consumeError } = await adminClient
            .rpc('consume_school_otp', {
                p_school_id: schoolId,
                p_phone: cleanPhone,
                p_code: cleanCode,
                p_max_attempts: MAX_VERIFY_ATTEMPTS,
            })
            .single()

        if (consumeError) throw consumeError

        const consumedResult = consumedOtp as {
            result_status?: string
            attempts_remaining?: number | string
        } | null
        const resultStatus = String(consumedResult?.result_status || 'missing_otp')
        const attemptsRemaining = Number(consumedResult?.attempts_remaining || 0)

        if (resultStatus !== 'verified') {
            return buildJsonResponse({
                success: false,
                status: resultStatus,
                attempts_remaining: attemptsRemaining,
                message: verificationError(resultStatus, attemptsRemaining),
            }, resultStatus === 'parent_not_allowed' ? 403 : 400)
        }

        let { data: userId } = await adminClient.rpc('get_user_id_by_phone', {
            p_phone: authPhone,
        })

        if (!userId) {
            const { data: fallbackUserId } = await adminClient.rpc('get_user_id_by_phone', {
                p_phone: cleanPhone,
            })
            userId = fallbackUserId
        }

        if (!userId) {
            throw new Error('User account lama helin.')
        }

        const oneTimePassword = generateSessionPassword()
        const { error: updatePasswordError } = await adminClient.auth.admin.updateUserById(
            userId,
            { password: oneTimePassword }
        )

        if (updatePasswordError) throw updatePasswordError

        const { data: signInData, error: signInError } = await authClient.auth.signInWithPassword({
            phone: authPhone,
            password: oneTimePassword,
        })

        if (signInError || !signInData.session) {
            throw signInError || new Error('Session lama abuuri karin.')
        }

        const { error: enrollmentError } = await adminClient.rpc('enroll_verified_device', {
            p_school_id: schoolId,
            p_phone: cleanPhone,
            p_device_id: cleanDeviceId,
            p_device_name: optionalDeviceText(device_name, 120),
            p_platform: optionalDeviceText(platform, 32),
            p_app_variant: optionalDeviceText(app_variant, 64),
        })

        if (enrollmentError) throw enrollmentError

        const nowIso = new Date().toISOString()
        await adminClient.from('otp_logs').insert({
            school_id: schoolId,
            phone: cleanPhone,
            status: 'VERIFIED',
            provider: 'whatsapp',
            message: 'OTP verified and authenticated session issued.',
            sent_at: nowIso,
        })

        return buildJsonResponse({
            success: true,
            status: 'verified',
            school_id: schoolId,
            phone: authPhone,
            session: {
                access_token: signInData.session.access_token,
                refresh_token: signInData.session.refresh_token,
                expires_at: signInData.session.expires_at,
                expires_in: signInData.session.expires_in,
                token_type: signInData.session.token_type,
            },
        })
    } catch (error: any) {
        console.error('[VERIFY OTP Error]', error.message)
        return buildJsonResponse({
            success: false,
            error: 'OTP verification-ku ma guulaysan. Fadlan mar kale isku day.',
        }, 400)
    }
})
