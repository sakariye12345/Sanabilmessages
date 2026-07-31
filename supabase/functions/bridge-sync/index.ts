// Follow this setup guide to integrate the Deno language server with your editor:
// https://deno.land/manual/getting_started/setup_your_environment
// This enables autocomplete, go to definition, etc.

import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { normalizeSomaliPhone } from '../_shared/phone.ts'
import { isAuthorizedInternalRequest } from '../_shared/internal.ts'

// Environment variables for Supabase (Service Role is required for RLS bypass)
const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
const EXPO_PUSH_API = 'https://exp.host/--/api/v2/push/send'
const EXPO_RECEIPTS_API = 'https://exp.host/--/api/v2/push/getReceipts'
const EXPO_ACCESS_TOKEN = Deno.env.get('EXPO_ACCESS_TOKEN') || ''

const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY)

const corsHeaders = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
}

interface School {
    id: number
    name: string
    ci3_url: string
    ci3_token?: string | null
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

    if (!isAuthorizedInternalRequest(req)) {
        return new Response(
            JSON.stringify({ error: 'Unauthorized' }),
            { status: 401, headers: { ...corsHeaders, 'Content-Type': 'application/json' } },
        )
    }

    try {
        const results = {
            schools_processed: 0,
            downstream: 0,
            upstream: 0,
            push_receipts_checked: 0,
            errors: [] as string[]
        }

        // 1. Fetch Active Schools
        const { data: schools, error: schoolError } = await supabase
            .rpc('get_active_school_integrations')

        if (schoolError || !schools) {
            throw new Error(`Failed to fetch schools: ${schoolError?.message}`)
        }

        // 2. Process Each School
        for (const school of schools) {
            try {
                const schoolErrorCountBefore = results.errors.length

                // Downstream (CI3 -> Supabase)
                const downCount = await processDownstream(school, results)

                // Upstream (Supabase -> CI3)
                const upCount = await processUpstream(school, results)

                results.schools_processed++

                const schoolErrorCount = results.errors.length - schoolErrorCountBefore
                if (schoolErrorCount > 0) {
                    await logSync(
                        school.id,
                        'PARTIAL',
                        `Processed ${downCount} down, ${upCount} up with ${schoolErrorCount} item error(s).`,
                    )
                } else if (downCount > 0 || upCount > 0) {
                    await logSync(school.id, 'SUCCESS', `Processed ${downCount} down, ${upCount} up.`)
                }

            } catch (e: any) {
                const msg = `Error in school ${school.name}: ${e.message}`
                results.errors.push(msg)
                // Log Failure
                await logSync(school.id, 'FAILED', msg, { stack: e.stack })
            }
        }

        try {
            results.push_receipts_checked = await processPushReceipts()
        } catch (error: any) {
            results.errors.push(`Push receipt processing failed: ${error.message}`)
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
        const controller = new AbortController()
        const timeout = setTimeout(() => controller.abort(), 20_000)

        try {
            const res = await fetch(url, { ...options, signal: controller.signal })
            if (!res.ok) {
                // If 5xx error, throw to retry. If 4xx (client error), maybe don't retry?
                // For now, retry all non-200 to be safe against flakes.
                if (res.status >= 500 || res.status === 429) throw new Error(`Status ${res.status}`)
                return res // Return 4xx immediately (don't retry auth errors)
            }
            return res
        } catch (e) {
            if (i === retries - 1) throw e // Final failure
            console.log(`Retrying ${url} (${i + 1}/${retries})...`)
            await new Promise(r => setTimeout(r, backoff * (i + 1))) // Wait 1s, 2s, 3s
        } finally {
            clearTimeout(timeout)
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
            if (!Array.isArray(ci3Msgs)) {
                throw new Error('CI3 messages response must be an array')
            }
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
        .select('id, status, ci3_id')
        .eq('is_synced_to_ci3', false)
        .eq('school_id', school.id)
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
    const rawCi3Id = String(msg.id ?? '').trim()
    const cleanPhone = normalizeSomaliPhone(msg.phone || '')
    const text = String(msg.message || '').replace(/<br\s*\/?>/gi, '\n').trim()

    if (!rawCi3Id || !cleanPhone || !text) {
        results.errors.push(`Invalid CI3 message payload (${school.name}, id: ${rawCi3Id || 'missing'})`)
        return false
    }

    const lowerText = text.toLowerCase()
    let type = 'general'
    let title = 'School Notice'

    if (['maqan', 'absent'].some(k => lowerText.includes(k))) { type = 'absence'; title = 'Absence Alert' }
    else if (['imtixaan', 'exam', 'natiijo'].some(k => lowerText.includes(k))) { type = 'exam'; title = 'Exam Result' }
    else if (['lacag', 'fee'].some(k => lowerText.includes(k))) { type = 'finance'; title = 'Fee Notice' }
    else if (['mahad', 'received', 'bixisay'].some(k => lowerText.includes(k))) { type = 'receipt'; title = 'Fee Receipt' }
    else if (['ogaysiis', 'notice', 'fasax', 'holiday'].some(k => lowerText.includes(k))) { type = 'notice'; title = 'School Notice' }

    const compositeCi3Id = `${rawCi3Id}-${type}`

    const { data: exactMatch, error: exactMatchError } = await supabase
        .from('message_recipients')
        .select('id')
        .eq('school_id', school.id)
        .eq('ci3_id', compositeCi3Id)
        .maybeSingle()

    if (exactMatchError) throw exactMatchError
    if (exactMatch) return false

    const { data: legacyMatch, error: legacyMatchError } = await supabase
        .from('message_recipients')
        .select('id, messages!inner(type)')
        .eq('school_id', school.id)
        .eq('ci3_id', rawCi3Id)
        .maybeSingle()

    if (legacyMatchError) throw legacyMatchError
    if (legacyMatch) {
        const legacyMessage = Array.isArray(legacyMatch.messages)
            ? legacyMatch.messages[0]
            : legacyMatch.messages
        const legacyType = legacyMessage?.type
        if (legacyType === type) return false
    }

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
            }
        }
    }

    if (!targetMessage) {
        results.errors.push(`Msg Insert Error (${school.name}): ${msgError?.message}`)
        return false
    }

    const { error: rcptError } = await supabase
        .from('message_recipients')
        .insert({
            school_id: school.id,
            message_id: targetMessage.id,
            phone_number: cleanPhone,
            status: 'pending',
            ci3_id: compositeCi3Id,
            is_synced_to_ci3: false
        })

    if (!rcptError) {
        results.downstream++
        await sendPushNotification(
            school.id,
            targetMessage.id,
            cleanPhone,
            title,
            text,
            school.name,
        )

        return true
    } else {
        if (newMsg?.id) {
            await supabase
                .from('messages')
                .delete()
                .eq('id', newMsg.id)
        }
        if (rcptError.code !== '23505') {
            results.errors.push(`Recipient Insert Error (${school.name}): ${rcptError.message}`)
        }
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
async function sendPushNotification(
    schoolId: number,
    messageId: number,
    phone: string,
    title: string,
    body: string,
    schoolName: string,
) {
    try {
        const { data: devices } = await supabase
            .from('user_devices')
            .select('id, fcm_token')
            .eq('school_id', schoolId)
            .eq('phone_number', phone)
            .eq('is_active', true)
            .is('revoked_at', null)

        if (!devices || devices.length === 0) return;

        const deliveries = [];
        for (const device of devices) {
            if (
                !device.fcm_token ||
                !/^(ExponentPushToken|ExpoPushToken)\[.+\]$/.test(device.fcm_token)
            ) continue;

            const shortBody = body.length > 150 ? body.substring(0, 147) + '...' : body;

            deliveries.push({
                userDeviceId: device.id,
                payload: {
                    to: device.fcm_token,
                    sound: 'default',
                    title: `${schoolName}: ${title}`,
                    body: shortBody,
                    data: {
                        message_id: messageId,
                        school_id: schoolId,
                        type: 'new_message',
                    },
                },
            });
        }

        if (deliveries.length === 0) return;

        for (let offset = 0; offset < deliveries.length; offset += 100) {
            const batch = deliveries.slice(offset, offset + 100)
            const headers: Record<string, string> = {
                Accept: 'application/json',
                'Accept-encoding': 'gzip, deflate',
                'Content-Type': 'application/json',
            }
            if (EXPO_ACCESS_TOKEN) {
                headers.Authorization = `Bearer ${EXPO_ACCESS_TOKEN}`
            }

            const response = await fetchWithRetry(EXPO_PUSH_API, {
                method: 'POST',
                headers,
                body: JSON.stringify(batch.map((delivery) => delivery.payload)),
            }, 3, 1000)

            if (!response.ok) {
                console.error(`Expo Push API failed with status ${response.status}`);
                continue
            }

            const responseBody = await response.json()
            const tickets = Array.isArray(responseBody?.data) ? responseBody.data : []

            for (let index = 0; index < batch.length; index += 1) {
                const delivery = batch[index]
                const ticket = tickets[index]

                if (ticket?.status === 'ok' && ticket.id) {
                    const { error: ticketError } = await supabase
                        .from('push_delivery_tickets')
                        .upsert({
                            expo_ticket_id: ticket.id,
                            school_id: schoolId,
                            message_id: messageId,
                            user_device_id: delivery.userDeviceId,
                            status: 'PENDING',
                            updated_at: new Date().toISOString(),
                        }, { onConflict: 'expo_ticket_id' })

                    if (ticketError) {
                        console.error('[Notification] Ticket persistence failed:', ticketError.message)
                    }
                    continue
                }

                const errorCode = ticket?.details?.error || 'ExpoTicketError'
                console.error(`[Notification] Push ticket failed: ${errorCode}`)
                if (errorCode === 'DeviceNotRegistered') {
                    await clearInvalidPushToken(delivery.userDeviceId)
                }
            }
        }

        console.log(`[Notification] Submitted ${deliveries.length} token(s) for ${phone}`);
    } catch (e: any) {
        console.error(`[Notification] Push API Exception: ${e.message}`);
    }
}

async function clearInvalidPushToken(userDeviceId: number) {
    const { error } = await supabase
        .from('user_devices')
        .update({ fcm_token: null, updated_at: new Date().toISOString() })
        .eq('id', userDeviceId)

    if (error) throw error
}

async function processPushReceipts() {
    const now = new Date()
    const receiptCutoff = new Date(now.getTime() - 15 * 60 * 1000).toISOString()
    const receiptExpiry = new Date(now.getTime() - 24 * 60 * 60 * 1000).toISOString()

    const { error: expiryError } = await supabase
        .from('push_delivery_tickets')
        .update({
            status: 'FAILED',
            error_code: 'ReceiptTimeout',
            error_message: 'Expo receipt was not available within 24 hours.',
            receipt_checked_at: now.toISOString(),
            updated_at: now.toISOString(),
        })
        .eq('status', 'PENDING')
        .lt('created_at', receiptExpiry)

    if (expiryError) throw expiryError

    const { data: pendingTickets, error } = await supabase
        .from('push_delivery_tickets')
        .select('id, expo_ticket_id, user_device_id')
        .eq('status', 'PENDING')
        .lte('created_at', receiptCutoff)
        .order('created_at', { ascending: true })
        .limit(1000)

    if (error) throw error
    if (!pendingTickets?.length) return 0

    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    }
    if (EXPO_ACCESS_TOKEN) {
        headers.Authorization = `Bearer ${EXPO_ACCESS_TOKEN}`
    }

    const response = await fetchWithRetry(EXPO_RECEIPTS_API, {
        method: 'POST',
        headers,
        body: JSON.stringify({ ids: pendingTickets.map((ticket) => ticket.expo_ticket_id) }),
    }, 3, 1000)

    if (!response.ok) {
        throw new Error(`Expo receipt API returned ${response.status}`)
    }

    const responseBody = await response.json()
    const receipts = responseBody?.data || {}
    let checked = 0

    for (const ticket of pendingTickets) {
        const receipt = receipts[ticket.expo_ticket_id]
        if (!receipt) continue

        const delivered = receipt.status === 'ok'
        const errorCode = delivered ? null : receipt?.details?.error || 'ExpoReceiptError'
        const { error: updateError } = await supabase
            .from('push_delivery_tickets')
            .update({
                status: delivered ? 'DELIVERED' : 'FAILED',
                error_code: errorCode,
                error_message: delivered ? null : receipt?.message || 'Push delivery failed.',
                receipt_checked_at: now.toISOString(),
                updated_at: now.toISOString(),
            })
            .eq('id', ticket.id)
            .eq('status', 'PENDING')

        if (updateError) throw updateError
        if (errorCode === 'DeviceNotRegistered') {
            await clearInvalidPushToken(ticket.user_device_id)
        }
        checked += 1
    }

    return checked
}
