import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { normalizeSomaliPhone, toE164SomaliPhone } from '../_shared/phone.ts'

const corsHeaders = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
}

Deno.serve(async (req) => {
    if (req.method === 'OPTIONS') {
        return new Response('ok', { headers: corsHeaders })
    }

    const supabase = createClient(
        Deno.env.get('SUPABASE_URL') ?? '',
        Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    )

    try {
        const { phone } = await req.json()

        if (!phone) throw new Error("Phone number required")

        const cleanPhone = normalizeSomaliPhone(phone)
        if (!cleanPhone) throw new Error("Phone number required")

        // ─────────────────────────────────────────────
        // 1. Find which school this parent belongs to
        // ─────────────────────────────────────────────
        const { data: parent, error: parentError } = await supabase
            .from('allowed_parents')
            .select('school_id, phone_number')
            .eq('phone_number', cleanPhone)
            .eq('is_active', true)
            .single()

        if (parentError || !parent) {
            console.error(`Parent not found for ${cleanPhone}:`, parentError?.message)
            throw new Error("Lambarkan nidaamka kuma jiro. La xidhiidh maamulka.")
        }

        // ─────────────────────────────────────────────
        // 2. Fetch school's OTP gateway config
        // ─────────────────────────────────────────────
        const { data: school, error: schoolError } = await supabase
            .from('schools')
            .select('id, name, otp_gateway_url, otp_gateway_key, otp_sender_id')
            .eq('id', parent.school_id)
            .single()

        if (schoolError || !school) {
            throw new Error(`School config not found for school_id: ${parent.school_id}`)
        }

        // ─────────────────────────────────────────────
        // 3. Generate 6-digit OTP code
        // ─────────────────────────────────────────────
        const code = Math.floor(100000 + Math.random() * 900000).toString()
        console.log(`[OTP] Generating for ${cleanPhone} (${school.name}): ${code}`)

        // ─────────────────────────────────────────────
        // 4. Create/Update Supabase Auth user
        //    (Code is used as temporary password)
        // ─────────────────────────────────────────────
        let userId = ''
        const authPhone = toE164SomaliPhone(phone)
        if (!authPhone) throw new Error("Phone number required")

        const { data: createdUser, error: createError } = await supabase.auth.admin.createUser({
            phone: authPhone,
            password: code,
            email_confirm: true,
            phone_confirm: true
        })

        if (createError) {
            // User already exists — get their ID and update password
            console.log("User exists, finding ID via RPC...")
            let { data: uid } = await supabase.rpc('get_user_id_by_phone', { p_phone: authPhone })

            // Fallback: try without '+'
            if (!uid) {
                const { data: uid2 } = await supabase.rpc('get_user_id_by_phone', { p_phone: cleanPhone })
                uid = uid2
            }

            if (!uid) throw new Error("User conflict but could not find user ID.")

            userId = uid
            const { error: updateError } = await supabase.auth.admin.updateUserById(userId, { password: code })
            if (updateError) throw updateError
        } else {
            userId = createdUser.user.id
        }

        // ─────────────────────────────────────────────
        // 5. Queue OTP for delivery (Python dispatcher reads this)
        // ─────────────────────────────────────────────
        const { error: queueError } = await supabase
            .from('otp_queue')
            .insert({
                phone: cleanPhone,
                code,
                school_id: school.id,
                status: 'PENDING',
                provider: 'whatsapp',
                updated_at: new Date().toISOString(),
            })

        if (queueError) throw queueError

        // ─────────────────────────────────────────────
        // 6. If school has a gateway configured, call it directly
        //    Otherwise: falls back to Python dispatcher (WhatsApp)
        // ─────────────────────────────────────────────
        if (school.otp_gateway_url && school.otp_gateway_key) {
            console.log(`[OTP] Sending via school gateway: ${school.otp_gateway_url}`)
            try {
                const msgBody = `*${school.name}*\nKoodkaaga gelitaanka (OTP):\n\n*${code}*\n\nWuxuu ansax yahay 10 daqiiqo.`
                await fetch(school.otp_gateway_url, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${school.otp_gateway_key}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        to: cleanPhone,
                        from: school.otp_sender_id ?? school.name,
                        message: msgBody
                    })
                })
                // Mark as SENT immediately if gateway returns success
                await supabase.from('otp_queue')
                    .update({
                        status: 'SENT',
                        provider: 'gateway',
                        sent_at: new Date().toISOString(),
                        updated_at: new Date().toISOString(),
                    })
                    .eq('phone', cleanPhone)
                    .eq('code', code)
            } catch (gwErr: any) {
                console.error("[OTP] Gateway error (non-blocking):", gwErr.message)
                // Falls through — Python dispatcher will retry
            }
        } else {
            console.log(`[OTP] No gateway configured for ${school.name}. Python dispatcher will handle.`)
        }

        return new Response(
            JSON.stringify({ success: true, message: 'OTP queued' }),
            { headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
        )

    } catch (error: any) {
        console.error("[OTP Error]", error.message)
        return new Response(
            JSON.stringify({ error: error.message }),
            { status: 400, headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
        )
    }
})
