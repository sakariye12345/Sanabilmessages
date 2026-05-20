// Follow this setup guide to integrate the Deno language server with your editor:
// https://deno.land/manual/getting_started/setup_your_environment
// This enables autocomplete, go to definition, etc.

import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { normalizeSomaliPhone } from '../_shared/phone.ts'

// Environment variables for Supabase (Service Role is required for RLS bypass)
const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!

const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY)

const corsHeaders = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
}

console.log("Hello from Multi-School Bridge Sync (Production v2)!")

interface School {
    id: number
    name: string
    ci3_url: string
    ci3_token: string
    messages_api_url?: string | null
    messages_api_token?: string | null
}

function trimTrailingSlash(value: string): string {
    return value.replace(/\/+$/, '')
}

function resolveMessagesBaseUrl(school: School): string {
    const raw = school.messages_api_url || school.ci3_url
    if (!raw) {
        throw new Error(`School ${school.name} is missing a message API URL`)
    }
    return trimTrailingSlash(raw)
}

function resolveMessagesToken(school: School): string {
    const token = school.messages_api_token || school.ci3_token
    if (!token) {
        throw new Error(`School ${school.name} is missing a message API token`)
    }
    return token
}

Deno.serve(async (req) => {
    if (req.method === 'OPTIONS') {
        return new Response('ok', { headers: corsHeaders })
    }

    try {
        const results = {
            schools_processed: 0,
            downstream: 0,
            upstream: 0,
            errors: [] as string[]
        }

        // 1. Fetch Active Schools
        const { data: schools, error: schoolError } = await supabase
            .from('schools')
            .select('*')
            .eq('is_active', true)

        if (schoolError || !schools) {
            throw new Error(`Failed to fetch schools: ${schoolError?.message}`)
        }

        // 2. Process Each School
        for (const school of schools) {
            try {
                // Downstream (CI3 -> Supabase)
                const downCount = await processDownstream(school, results)

                // Upstream (Supabase -> CI3)
                const upCount = await processUpstream(school, results)

                results.schools_processed++

                // Log Success
                await logSync(school.id, 'SUCCESS', `Processed ${downCount} down, ${upCount} up.`)

            } catch (e: any) {
                const msg = `Error in school ${school.name}: ${e.message}`
                results.errors.push(msg)
                // Log Failure
                await logSync(school.id, 'FAILED', msg, { stack: e.stack })
            }
        }

        return new Response(
            JSON.stringify(results),
            { headers: { ...corsHeaders, 'Content-Type': 'application/json' } },
        )
    } catch (error: any) {
        return new Response(
            JSON.stringify({ error: error.message }),
            { headers: { ...corsHeaders, 'Content-Type': 'application/json' }, status: 500 },
        )
    }
})

// --- HELPERS ---

// Retry Function (Exponential Backoff)
async function fetchWithRetry(url: string, options: any, retries = 3, backoff = 1000) {
    for (let i = 0; i < retries; i++) {
        try {
            const res = await fetch(url, options)
            if (!res.ok) {
                // If 5xx error, throw to retry. If 4xx (client error), maybe don't retry?
                // For now, retry all non-200 to be safe against flakes.
                if (res.status >= 500) throw new Error(`Status ${res.status}`)
                return res // Return 4xx immediately (don't retry auth errors)
            }
            return res
        } catch (e) {
            if (i === retries - 1) throw e // Final failure
            console.log(`Retrying ${url} (${i + 1}/${retries})...`)
            await new Promise(r => setTimeout(r, backoff * (i + 1))) // Wait 1s, 2s, 3s
        }
    }
    throw new Error(`Failed after ${retries} retries`)
}

// Logging Function (inserts into sync_logs)
async function logSync(schoolId: number, status: 'SUCCESS' | 'FAILED' | 'PARTIAL', message: string, details: any = {}) {
    try {
        await supabase.from('sync_logs').insert({
            school_id: schoolId,
            status,
            message,
            details
        })
    } catch (e) {
        console.error("Failed to write to sync_logs:", e)
    }
}


// --- CORE LOGIC ---

// HELPER: Downstream (CI3 -> Supabase)
async function processDownstream(school: School, results: any): Promise<number> {
    let count = 0
    try {
        const baseUrl = resolveMessagesBaseUrl(school)
        const token = resolveMessagesToken(school)
        const ci3Resp = await fetchWithRetry(`${baseUrl}/messages/contacts`, {
            headers: {
                'Authorization': token,
                'Content-Type': 'application/json'
            }
        })

        if (ci3Resp.ok) {
            const ci3Msgs = await ci3Resp.json()
            const pending = ci3Msgs.filter((m: any) => !['sent', 'failed'].includes(m.sent_status))

            for (const msg of pending) {
                const success = await processSingleMessage(msg, school, results)
                if (success) count++
            }
        } else {
            throw new Error(`CI3 HTTP Error: ${ci3Resp.status}`)
        }
    } catch (e: any) {
        throw new Error(`Downstream Sync Failed: ${e.message}`)
    }
    return count
}

// HELPER: Upstream (Supabase -> CI3)
async function processUpstream(school: School, results: any): Promise<number> {
    let count = 0
    // Only fetch Status Updates for THIS school's messages
    const { data: updates, error } = await supabase
        .from('message_recipients')
        .select('id, status, ci3_id, messages!inner(school_id)') // Join to filter by school
        .eq('is_synced_to_ci3', false)
        .eq('messages.school_id', school.id)
        .not('ci3_id', 'is', null)

    if (error) throw new Error(`Supabase Fetch Error: ${error.message}`)

    if (updates && updates.length > 0) {
        for (const update of updates) {
            const success = await updateCi3Status(update, school, results)
            if (success) count++
        }
    }
    return count
}

// HELPER: Process a single CI3 message
async function processSingleMessage(msg: any, school: School, results: any): Promise<boolean> {
    // 1. Determine Type/Title FIRST (Used for Composite ID)
    const text = (msg.message || '').replace('<br />', '\n')
    const lowerText = text.toLowerCase()
    let type = 'general'
    let title = 'School Notice'

    if (['maqan', 'absent'].some(k => lowerText.includes(k))) { type = 'absence'; title = 'Absence Alert' }
    else if (['imtixaan', 'exam', 'natiijo'].some(k => lowerText.includes(k))) { type = 'exam'; title = 'Exam Result' }
    else if (['lacag', 'fee'].some(k => lowerText.includes(k))) { type = 'finance'; title = 'Fee Notice' }
    else if (['mahad', 'received', 'bixisay'].some(k => lowerText.includes(k))) { type = 'receipt'; title = 'Fee Receipt' }
    else if (['ogaysiis', 'notice', 'fasax', 'holiday'].some(k => lowerText.includes(k))) { type = 'notice'; title = 'School Notice' }

    // 2. Generate Composite ID to avoid collisions (e.g., "501-finance")
    const rawCi3Id = String(msg.id)
    const compositeCi3Id = `${rawCi3Id}-${type}`

    // 3. Check Duplicates (Idempotency) - scoped to this school
    await logSync(school.id, 'PARTIAL', `Checking dupes for ${compositeCi3Id} (Legacy: ${rawCi3Id})...`)

    // First, check exact composite match
    const { data: exactMatch } = await supabase
        .from('message_recipients')
        .select('id, messages!inner(school_id)')
        .eq('ci3_id', compositeCi3Id)
        .eq('messages.school_id', school.id)
        .maybeSingle()

    if (exactMatch) {
        await logSync(school.id, 'PARTIAL', `SKIP: Exact match found for ${compositeCi3Id}`)
        return false
    }

    // Second, check legacy match
    const { data: legacyMatch } = await supabase
        .from('message_recipients')
        .select('id, messages!inner(type)')
        .eq('ci3_id', rawCi3Id)
        .eq('messages.school_id', school.id)
        .maybeSingle()

    if (legacyMatch) {
        // @ts-ignore
        const legacyType = legacyMatch.messages?.type
        await logSync(school.id, 'PARTIAL', `Legacy match ${rawCi3Id}. Legacy: ${legacyType}, New: ${type}`)

        if (legacyType === type) {
            await logSync(school.id, 'PARTIAL', `SKIP: Legacy type matches new type.`)
            return false
        }
        // If types differ, ALLOW IT.
        await logSync(school.id, 'PARTIAL', `ALLOW: Types differ.`)
    }

    // 4. Insert Message Body (Linked to School)
    const { data: newMsg, error: msgError } = await supabase
        .from('messages')
        .insert({
            school_id: school.id,
            type,
            title,
            body: text
        })
        .select()
        .single()

    let targetMessage = newMsg

    if (msgError) {
        const duplicateDetected = msgError.message?.includes('Duplicate message detected within 5 minutes')

        if (duplicateDetected) {
            const { data: existingMsg, error: existingMsgError } = await supabase
                .from('messages')
                .select('id, school_id, type, title, body')
                .eq('school_id', school.id)
                .eq('type', type)
                .eq('title', title)
                .eq('body', text)
                .order('id', { ascending: false })
                .limit(1)
                .maybeSingle()

            if (!existingMsgError && existingMsg) {
                targetMessage = existingMsg
                await logSync(school.id, 'PARTIAL', `Reusing existing message for CI3 ${rawCi3Id}`, {
                    message_id: existingMsg.id,
                })
            }
        }
    }

    if (!targetMessage) {
        await logSync(school.id, 'PARTIAL', `Message insert failed for CI3 ${rawCi3Id}`, {
            error: msgError?.message || 'unknown',
            phone: msg.phone || null,
        })
        results.errors.push(`Msg Insert Error (${school.name}): ${msgError?.message}`)
        return false
    }

    // 5. Insert Recipient
    const cleanPhone = normalizeSomaliPhone(msg.phone || '')
    if (!cleanPhone) {
        results.errors.push(`Recipient phone missing or invalid for CI3 message ${rawCi3Id}`)
        return false
    }

    const { error: rcptError } = await supabase
        .from('message_recipients')
        .insert({
            message_id: targetMessage.id,
            phone_number: cleanPhone,
            status: 'pending',
            ci3_id: compositeCi3Id,
            is_synced_to_ci3: false
        })

    if (!rcptError) {
        results.downstream++

        // 🔥 Fire and Forget Push Notification
        // Don't await heavily, let it run asynchronously (or await lightly) so it doesn't block the sync loop
        await sendPushNotification(cleanPhone, title, text, school.name)

        return true
    } else {
        await logSync(school.id, 'PARTIAL', `Recipient insert failed for CI3 ${rawCi3Id}`, {
            error: rcptError.message,
            phone: cleanPhone,
            message_id: targetMessage.id,
        })
        if (newMsg?.id) {
            await supabase
                .from('messages')
                .delete()
                .eq('id', newMsg.id)
        }
        console.error(`Recipient Insert Error (${school.name})`, rcptError)
        return false
    }
}

// HELPER: Update CI3 Status
async function updateCi3Status(item: any, school: School, results: any): Promise<boolean> {
    let ci3Status = item.status
    if (ci3Status === 'seen') ci3Status = 'sent'

    // Extract Raw ID from Composite ID (e.g. "501-finance" -> "501")
    // If it's a legacy ID "501", it returns "501". Safe.
    const rawCi3Id = item.ci3_id.split('-')[0]

    try {
        const baseUrl = resolveMessagesBaseUrl(school)
        const token = resolveMessagesToken(school)
        const resp = await fetchWithRetry(`${baseUrl}/messages/update_status`, {
            method: 'POST',
            headers: {
                'Authorization': token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                contact_id: rawCi3Id, // Send Raw ID to CI3
                sent_status: ci3Status
            })
        }, 3, 1000)

        if (resp.ok) {
            await supabase
                .from('message_recipients')
                .update({ is_synced_to_ci3: true })
                .eq('id', item.id)

            results.upstream++
            return true
        }
    } catch (e: any) {
        // console.error(`CI3 Update Failed (${school.name})`, e)
        // We generally don't want to log every single status update failure as a critical error in sync_logs
        // unless it happens frequently. For now, let's keep it silent or log to console.
        return false
    }
    return false
}

// HELPER: Send Expo Push Notification
async function sendPushNotification(phone: string, title: string, body: string, schoolName: string) {
    try {
        // 1. Fetch active push tokens for this phone number
        const { data: devices } = await supabase
            .from('user_devices')
            .select('fcm_token')
            .eq('phone_number', phone)
            .eq('is_active', true)

        if (!devices || devices.length === 0) return;

        // 2. Prepare Expo Push API payload
        const messages = [];
        for (const device of devices) {
            if (!device.fcm_token) continue;
            // Truncate body if very long
            const shortBody = body.length > 150 ? body.substring(0, 147) + '...' : body;

            messages.push({
                to: device.fcm_token,
                sound: 'default',
                title: `${schoolName}: ${title}`,
                body: shortBody,
                data: { phone, type: 'new_message' },
            });
        }

        if (messages.length === 0) return;

        // 3. Send out via Expo HTTP HTTP/2 API
        const response = await fetch('https://exp.host/--/api/v2/push/send', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Accept-encoding': 'gzip, deflate',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(messages),
        });

        if (!response.ok) {
            console.error(`Expo Push API failed with status ${response.status}`);
        } else {
            console.log(`[Notification] Pushed ${messages.length} token(s) to ${phone}`);
        }
    } catch (e: any) {
        console.error(`[Notification] Push API Exception: ${e.message}`);
    }
}
