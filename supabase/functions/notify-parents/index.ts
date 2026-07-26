import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { isAuthorizedInternalRequest } from '../_shared/internal.ts'

const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
const EXPO_PUSH_API = 'https://exp.host/--/api/v2/push/send'

const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY)

Deno.serve(async (req) => {
    if (!isAuthorizedInternalRequest(req, 'NOTIFY_WEBHOOK_SECRET')) {
        return new Response(JSON.stringify({ error: 'Unauthorized' }), {
            status: 401,
            headers: { 'Content-Type': 'application/json' },
        })
    }

    try {
        const payload = await req.json()
        const record = payload.record

        if (!record?.id || !record?.phone_number || !record?.message_id || !record?.school_id) {
            return new Response(JSON.stringify({ error: 'Invalid webhook record' }), {
                status: 400,
                headers: { 'Content-Type': 'application/json' },
            })
        }

        const { data: messageData, error: messageError } = await supabase
            .from('messages')
            .select('id, school_id, title, body, type, schools(name)')
            .eq('id', record.message_id)
            .eq('school_id', record.school_id)
            .single()

        if (messageError || !messageData) {
            return new Response(JSON.stringify({ error: 'Message not found in recipient school' }), {
                status: 404,
                headers: { 'Content-Type': 'application/json' },
            })
        }

        const { data: devices, error: deviceError } = await supabase
            .from('user_devices')
            .select('fcm_token')
            .eq('school_id', record.school_id)
            .eq('phone_number', record.phone_number)
            .eq('is_active', true)
            .is('revoked_at', null)
            .not('fcm_token', 'is', null)

        if (deviceError) throw deviceError

        const tokens = (devices || [])
            .map((device: any) => device.fcm_token)
            .filter((token: string) => /^(ExponentPushToken|ExpoPushToken)\[.+\]$/.test(token))

        if (tokens.length === 0) {
            return new Response(JSON.stringify({ sent: 0, reason: 'no_active_push_tokens' }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            })
        }

        const schoolRelation = Array.isArray(messageData.schools)
            ? messageData.schools[0]
            : messageData.schools
        const schoolName = schoolRelation?.name || 'School Notice'
        const shortBody = messageData.body.length > 150
            ? `${messageData.body.slice(0, 147)}...`
            : messageData.body

        const pushPayload = tokens.map((token: string) => ({
            to: token,
            title: `${schoolName}: ${messageData.title}`,
            body: shortBody,
            data: {
                message_id: messageData.id,
                school_id: messageData.school_id,
                type: messageData.type,
            },
            sound: 'default',
        }))

        const controller = new AbortController()
        const timeout = setTimeout(() => controller.abort(), 15_000)

        try {
            const response = await fetch(EXPO_PUSH_API, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Accept-encoding': 'gzip, deflate',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(pushPayload),
                signal: controller.signal,
            })

            if (!response.ok) {
                throw new Error(`Expo Push API returned ${response.status}`)
            }
        } finally {
            clearTimeout(timeout)
        }

        return new Response(JSON.stringify({ sent: pushPayload.length }), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
        })
    } catch (error: any) {
        console.error('[notify-parents]', error.message)
        return new Response(JSON.stringify({ error: 'Notification delivery failed' }), {
            status: 500,
            headers: { 'Content-Type': 'application/json' },
        })
    }
})
