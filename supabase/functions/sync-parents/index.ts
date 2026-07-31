import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { normalizeSomaliPhone } from '../_shared/phone.ts'
import { isAuthorizedInternalRequest } from '../_shared/internal.ts'

// Environment variables for Supabase (Service Role is required for RLS bypass)
const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
const configuredMinRatio = Number(Deno.env.get('PARENT_SYNC_MIN_RATIO') || 0.5)
const PARENT_SYNC_MIN_RATIO = Number.isFinite(configuredMinRatio)
    ? Math.min(1, Math.max(0.1, configuredMinRatio))
    : 0.5

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
    parents_api_url?: string | null
    parents_api_token?: string | null
}

function trimTrailingSlash(value: string): string {
    return value.replace(/\/+$/, '')
}

function resolveParentsBaseUrl(school: School): string {
    const raw = school.parents_api_url || school.ci3_url
    if (!raw) {
        throw new Error(`School ${school.name} is missing a parents API URL`)
    }
    return trimTrailingSlash(raw)
}

function resolveParentsToken(school: School): string {
    const token = school.parents_api_token || school.ci3_token
    if (!token) {
        throw new Error(`School ${school.name} is missing a parents API token`)
    }
    return token
}

async function fetchWithTimeout(url: string, init: RequestInit, timeoutMs = 20_000) {
    const controller = new AbortController()
    const timeout = setTimeout(() => controller.abort(), timeoutMs)

    try {
        return await fetch(url, { ...init, signal: controller.signal })
    } finally {
        clearTimeout(timeout)
    }
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
            parents_upserted: 0,
            parents_deactivated: 0,
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
                // Fetch from CI3 API
                const endpoint = `${resolveParentsBaseUrl(school)}/index.php/api/v1/parents/allowed`;
                const response = await fetchWithTimeout(endpoint, {
                    headers: {
                        'Authorization': `Bearer ${resolveParentsToken(school)}`,
                        'Content-Type': 'application/json'
                    }
                })

                if (!response.ok) {
                    throw new Error(`CI3 API Error: ${response.status} ${response.statusText}`);
                }

                const json = await response.json()
                if (!Array.isArray(json.data)) {
                    throw new Error('CI3 parents response must contain a data array')
                }

                const parents = json.data
                const normalizedParents = parents.flatMap((parent: any) => {
                    const normalizedPhone = normalizeSomaliPhone(parent.phone_number || '')
                    if (!normalizedPhone) {
                        results.errors.push(`Parent ${parent.parent_id} (${school.name}) has no valid phone number.`)
                        return []
                    }

                    return [{
                        parent_id: parent.parent_id,
                        parent_name: parent.parent_name,
                        phone_number: normalizedPhone,
                        is_active: parent.is_active !== false,
                    }]
                })

                console.log(`School ${school.name} returned ${parents.length} parents.`);

                const { count: currentActiveCount, error: countError } = await supabase
                    .from('allowed_parents')
                    .select('id', { count: 'exact', head: true })
                    .eq('school_id', school.id)
                    .eq('is_active', true)

                if (countError) throw countError
                if ((currentActiveCount || 0) > 0 && normalizedParents.length === 0) {
                    throw new Error('Safety stop: CI3 returned zero valid parents for a non-empty school')
                }

                const activeIncomingCount = normalizedParents.filter((parent: any) => parent.is_active).length
                if (
                    (currentActiveCount || 0) >= 10 &&
                    activeIncomingCount / Number(currentActiveCount) < PARENT_SYNC_MIN_RATIO
                ) {
                    throw new Error(
                        `Safety stop: active parent snapshot dropped from ${currentActiveCount} ` +
                        `to ${activeIncomingCount} (minimum ratio ${PARENT_SYNC_MIN_RATIO}).`
                    )
                }

                const { data: replacement, error: replacementError } = await supabase
                    .rpc('replace_school_allowed_parents', {
                        p_school_id: school.id,
                        p_parents: normalizedParents,
                    })
                    .single()

                if (replacementError) throw replacementError

                const replacementResult = replacement as {
                    upserted_count?: number | string
                    deactivated_count?: number | string
                } | null

                results.parents_upserted += Number(replacementResult?.upserted_count || 0)
                results.parents_deactivated += Number(replacementResult?.deactivated_count || 0)
                results.schools_processed++

            } catch (e: any) {
                const msg = `Error in school ${school.name}: ${e.message}`
                results.errors.push(msg)
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
