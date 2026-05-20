import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
const EXPO_PUSH_API = 'https://exp.host/--/api/v2/push/send'

const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY)

console.log("Notify Parents Function Loaded (Multi-School)")

Deno.serve(async (req) => {
    // This function is intended to be called by a Database Webhook
    // The payload usually comes in as JSON: { type: 'INSERT', record: { ... }, ... }

    try {
        const payload = await req.json()
        console.log("Webhook Payload:", JSON.stringify(payload))

        const record = payload.record // The inserted row in 'message_recipients'

        if (!record || !record.phone_number) {
            return new Response("No record data", { status: 400 })
        }

        const { id, phone_number, message_id } = record

        // 1. Fetch Message Details (Title/Body) + Include School Name
        const { data: messageData } = await supabase
            .from('messages')
            .select(`
            *,
            schools ( name )
        `)
            .eq('id', message_id)
            .single()

        if (!messageData) {
            console.error("Message body not found for ID:", message_id)
            return new Response("Message body missing", { status: 404 })
        }

        const schoolName = messageData.schools?.name || 'School Notice'

        // 2. Fetch User Tokens
        const { data: devices } = await supabase
            .from('user_devices')
            .select('fcm_token')
            .eq('phone_number', phone_number)
            .eq('is_active', true)

        if (!devices || devices.length === 0) {
            console.log(`No active devices for ${phone_number}`)
            await updateStatus(id, 'failed', 'No active devices')
            return new Response("No devices found", { status: 200 })
        }

        // 3. Prepare Push Payload
        const pushPayload = devices.map((d: any) => ({
            to: d.fcm_token,
            title: messageData.title,
            body: `${schoolName}: ${messageData.body}`, // Prepend School Name for context
            data: {
                message_id: messageData.id,
                type: messageData.type,
                school_name: schoolName
            },
            sound: 'default'
        }))

        // 4. Send to Expo
        const resp = await fetch(EXPO_PUSH_API, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Accept-encoding': 'gzip, deflate',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(pushPayload),
        })

        if (resp.ok) {
            // We assume success for now (Expo returns tickets, sophisticated logic checks ticket status)
            await updateStatus(id, 'sent')
            return new Response("Notification sent", { status: 200 })
        } else {
            const errText = await resp.text()
            await updateStatus(id, 'failed', `Expo Error: ${resp.status}`)
            return new Response(`Expo API Error: ${errText}`, { status: 500 })
        }

    } catch (error) {
        console.error("Function Error:", error)
        return new Response(error.message, { status: 500 })
    }
})

async function updateStatus(id: number, status: string, errorMsg?: string) {
    const updatePayload: any = { status, sent_at: new Date().toISOString() }
    if (errorMsg) updatePayload.error = errorMsg

    await supabase
        .from('message_recipients')
        .update(updatePayload)
        .eq('id', id)
}
