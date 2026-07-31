import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { normalizeSomaliPhone, toE164SomaliPhone } from '../_shared/phone.ts'

const corsHeaders = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
}

const OTP_VALIDITY_MINUTES = 10

function buildJsonResponse(
    body: Record<string, unknown>,
    status = 200,
    extraHeaders: Record<string, string> = {},
) {
    return new Response(JSON.stringify(body), {
        status,
        headers: { ...corsHeaders, ...extraHeaders, 'Content-Type': 'application/json' },
    })
}

function generateSecretPassword() {
    return `otp-${crypto.randomUUID()}`
}

function generateOtpCode() {
    const range = 900_000
    const maxUnbiasedValue = 0x1_0000_0000 - (0x1_0000_0000 % range)
    const randomValue = new Uint32Array(1)

    do {
        crypto.getRandomValues(randomValue)
    } while (randomValue[0] >= maxUnbiasedValue)

    return String(100_000 + (randomValue[0] % range))
}

function resolveClientAddress(request: Request) {
    const directAddress =
        request.headers.get('cf-connecting-ip') ||
        request.headers.get('x-real-ip')
    if (directAddress) return directAddress.trim().slice(0, 128)

    const forwarded = request.headers.get('x-forwarded-for') || ''
    return (forwarded.split(',')[0]?.trim() || 'unknown').slice(0, 128)
}

async function hmacSha256(value: string, secret: string) {
    const encoder = new TextEncoder()
    const key = await crypto.subtle.importKey(
        'raw',
        encoder.encode(secret),
        { name: 'HMAC', hash: 'SHA-256' },
        false,
        ['sign'],
    )
    const signature = await crypto.subtle.sign('HMAC', key, encoder.encode(value))
    return Array.from(new Uint8Array(signature))
        .map((byte) => byte.toString(16).padStart(2, '0'))
        .join('')
}

Deno.serve(async (req) => {
    if (req.method === 'OPTIONS') {
        return new Response('ok', { headers: corsHeaders })
    }

    const serviceRoleKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    const supabase = createClient(
        Deno.env.get('SUPABASE_URL') ?? '',
        serviceRoleKey
    )

    try {
        const { phone, school_id } = await req.json()
        const schoolId = Number(school_id)

        if (!phone || !Number.isSafeInteger(schoolId) || schoolId <= 0) {
            throw new Error('Phone number and school_id are required')
        }

        const cleanPhone = normalizeSomaliPhone(phone)
        if (!cleanPhone) throw new Error('Phone number required')

        const rateSecret = Deno.env.get('OTP_RATE_LIMIT_PEPPER') || serviceRoleKey
        if (!rateSecret) throw new Error('OTP rate-limit secret is not configured')

        const [phoneHash, ipHash] = await Promise.all([
            hmacSha256(`${schoolId}:${cleanPhone}`, rateSecret),
            hmacSha256(`${schoolId}:${resolveClientAddress(req)}`, rateSecret),
        ])

        const { data: rateLimit, error: rateLimitError } = await supabase
            .rpc('consume_otp_request_rate_limit', {
                p_school_id: schoolId,
                p_phone_hash: phoneHash,
                p_ip_hash: ipHash,
                p_window_seconds: 60,
                p_phone_limit: 3,
                p_ip_limit: 20,
            })
            .single()

        if (rateLimitError) throw rateLimitError

        const rateResult = rateLimit as {
            allowed?: boolean
            retry_after_seconds?: number | string
        } | null

        if (!rateResult?.allowed) {
            const retryAfter = Math.max(1, Number(rateResult?.retry_after_seconds || 60))
            return buildJsonResponse({
                success: false,
                status: 'rate_limited',
                cooldown_seconds: retryAfter,
                message: 'Codsiyo badan ayaa la sameeyey. Fadlan wax yar kadib isku day.',
            }, 429, { 'Retry-After': String(retryAfter) })
        }

        const { data: parent, error: parentError } = await supabase
            .from('allowed_parents')
            .select('school_id, phone_number')
            .eq('school_id', schoolId)
            .eq('phone_number', cleanPhone)
            .eq('is_active', true)
            .single()

        if (parentError || !parent) {
            console.error(`[OTP] Parent not found for ${cleanPhone}:`, parentError?.message)
            throw new Error('Lambarkan nidaamka kuma jiro. La xidhiidh maamulka.')
        }

        const { data: school, error: schoolError } = await supabase
            .from('schools')
            .select(`
                id,
                name,
                otp_is_paused,
                otp_pause_reason,
                otp_pause_until,
                otp_cooldown_seconds,
                otp_daily_cap
            `)
            .eq('id', schoolId)
            .eq('is_active', true)
            .single()

        if (schoolError || !school) {
            throw new Error(`School config not found for school_id: ${parent.school_id}`)
        }

        if (school.otp_is_paused) {
            const pauseUntil = school.otp_pause_until ? new Date(school.otp_pause_until) : null
            const stillPaused = !pauseUntil || pauseUntil.getTime() > Date.now()

            if (stillPaused) {
                return buildJsonResponse({
                    success: false,
                    status: 'paused',
                    paused: true,
                    message: school.otp_pause_reason || 'OTP service-ku si ku meel gaar ah ayuu u hakad galay.',
                    pause_until: school.otp_pause_until ?? null,
                    cooldown_seconds: Number(school.otp_cooldown_seconds || 30),
                }, 429)
            }

            await supabase
                .from('schools')
                .update({
                    otp_is_paused: false,
                    otp_pause_reason: null,
                    otp_pause_until: null,
                })
                .eq('id', school.id)
        }

        const cooldownSeconds = Number(school.otp_cooldown_seconds || 30)
        const now = Date.now()
        const dailyCap = Math.max(1, Number(school.otp_daily_cap || 250))
        const todayUtc = new Date(now)
        todayUtc.setUTCHours(0, 0, 0, 0)

        const { count: dailyRequestCount, error: dailyCountError } = await supabase
            .from('otp_queue')
            .select('id', { count: 'exact', head: true })
            .eq('school_id', school.id)
            .gte('created_at', todayUtc.toISOString())

        if (dailyCountError) throw dailyCountError
        if ((dailyRequestCount || 0) >= dailyCap) {
            return buildJsonResponse({
                success: false,
                status: 'daily_cap_reached',
                paused: true,
                cooldown_seconds: cooldownSeconds,
                message: 'School-kan wuxuu gaaray xadka OTP-ga maanta. La xidhiidh maamulka.',
            }, 429)
        }

        const { data: recentActiveRequest, error: recentActiveError } = await supabase
            .from('otp_queue')
            .select('id, created_at, status')
            .eq('school_id', school.id)
            .eq('phone', cleanPhone)
            .in('status', ['PENDING', 'PROCESSING', 'SENT'])
            .order('created_at', { ascending: false })
            .limit(1)
            .maybeSingle()

        if (recentActiveError) throw recentActiveError

        if (recentActiveRequest) {
            const requestCreatedAt = new Date(recentActiveRequest.created_at).getTime()
            const expiresAt = requestCreatedAt + OTP_VALIDITY_MINUTES * 60 * 1000

            if (now < expiresAt) {
                const remainingSeconds = Math.max(1, Math.ceil((expiresAt - now) / 1000))
                return buildJsonResponse({
                    success: true,
                    status: 'existing_active',
                    queued: false,
                    reused: true,
                    provider: 'whatsapp',
                    cooldown_seconds: remainingSeconds,
                    message: `OTP hore ayaa kuu yaal oo wali shaqaynaya. Fadlan isticmaal kii ugu dambeeyay ama sug ilaa uu ka dhacayo.`,
                })
            }

            await supabase
                .from('otp_queue')
                .update({
                    status: 'FAILED',
                    error_message: 'OTP expired before a new request was created.',
                    updated_at: new Date(now).toISOString(),
                    provider: 'whatsapp',
                })
                .eq('id', recentActiveRequest.id)
        }

        const cooldownThreshold = new Date(now - cooldownSeconds * 1000).toISOString()

        const { data: recentRequest, error: recentRequestError } = await supabase
            .from('otp_queue')
            .select('id, created_at, status')
            .eq('school_id', school.id)
            .eq('phone', cleanPhone)
            .gte('created_at', cooldownThreshold)
            .order('created_at', { ascending: false })
            .limit(1)
            .maybeSingle()

        if (recentRequestError) throw recentRequestError

        if (recentRequest) {
            return buildJsonResponse({
                success: true,
                status: 'existing_active',
                queued: false,
                reused: true,
                provider: 'whatsapp',
                cooldown_seconds: cooldownSeconds,
                message: `OTP hore ayaa loo codsaday. Fadlan sug ${cooldownSeconds} ilbiriqsi ka hor intaadan mar kale codsan.`,
            })
        }

        const authPhone = toE164SomaliPhone(phone)
        if (!authPhone) throw new Error('Phone number required')

        const code = generateOtpCode()
        const invalidationPassword = generateSecretPassword()

        let userId = ''
        const { data: createdUser, error: createError } = await supabase.auth.admin.createUser({
            phone: authPhone,
            password: invalidationPassword,
            email_confirm: true,
            phone_confirm: true,
        })

        if (createError) {
            console.log('[OTP] User exists, finding ID via RPC...')
            let { data: uid } = await supabase.rpc('get_user_id_by_phone', { p_phone: authPhone })

            if (!uid) {
                const { data: fallbackUid } = await supabase.rpc('get_user_id_by_phone', { p_phone: cleanPhone })
                uid = fallbackUid
            }

            if (!uid) throw new Error('User conflict but could not find user ID.')

            userId = uid
            const { error: updateError } = await supabase.auth.admin.updateUserById(userId, { password: invalidationPassword })
            if (updateError) throw updateError
        } else {
            userId = createdUser.user.id
        }

        const nowIso = new Date(now).toISOString()
        const expiresAtIso = new Date(now + OTP_VALIDITY_MINUTES * 60 * 1000).toISOString()

        const { error: supersedeError } = await supabase
            .from('otp_queue')
            .update({
                status: 'FAILED',
                error_message: 'Superseded by a newer OTP request.',
                updated_at: nowIso,
                provider: 'whatsapp',
            })
            .eq('school_id', school.id)
            .eq('phone', cleanPhone)
            .in('status', ['PENDING', 'PROCESSING', 'SENT'])

        if (supersedeError) throw supersedeError

        const { error: queueError } = await supabase
            .from('otp_queue')
            .insert({
                phone: cleanPhone,
                code,
                school_id: school.id,
                status: 'PENDING',
                provider: 'whatsapp',
                updated_at: nowIso,
                expires_at: expiresAtIso,
                verify_attempt_count: 0,
            })

        if (queueError) throw queueError

        console.log(`[OTP] Queued for WhatsApp delivery only: ${cleanPhone} (${school.name})`)

        return buildJsonResponse({
            success: true,
            status: 'queued',
            queued: true,
            provider: 'whatsapp',
            school_id: school.id,
            cooldown_seconds: cooldownSeconds,
            expires_at: expiresAtIso,
            message: 'OTP queued for WhatsApp delivery.',
        })
    } catch (error: any) {
        console.error('[OTP Error]', error.message)
        return buildJsonResponse({ error: error.message }, 400)
    }
})
