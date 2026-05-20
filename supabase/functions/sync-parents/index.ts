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

console.log("Hello from sync-parents Edge Function!")

interface School {
    id: number
    name: string
    ci3_url: string
    ci3_token: string
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

Deno.serve(async (req) => {
    if (req.method === 'OPTIONS') {
        return new Response('ok', { headers: corsHeaders })
    }

    try {
        const results = {
            schools_processed: 0,
            parents_upserted: 0,
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
                // Fetch from CI3 API
                const endpoint = `${resolveParentsBaseUrl(school)}/index.php/api/v1/parents/allowed`;
                const response = await fetch(endpoint, {
                    headers: {
                        'Authorization': `Bearer ${resolveParentsToken(school)}`,
                        'Content-Type': 'application/json'
                    }
                })

                if (!response.ok) {
                    throw new Error(`CI3 API Error: ${response.status} ${response.statusText}`);
                }

                const json = await response.json();
                const parents = json.data || [];

                console.log(`School ${school.name} returned ${parents.length} parents.`);

                for (const parent of parents) {
                    const normalizedPhone = normalizeSomaliPhone(parent.phone_number || '')
                    if (!normalizedPhone) {
                        results.errors.push(`Parent ${parent.parent_id} (${school.name}) has no valid phone number.`)
                        continue
                    }

                    // Upsert to Supabase
                    const { error: upsertError } = await supabase
                        .from('allowed_parents')
                        .upsert({
                            school_id: school.id,
                            parent_id: parent.parent_id,
                            parent_name: parent.parent_name,
                            phone_number: normalizedPhone,
                            is_active: parent.is_active,
                            last_sync_at: new Date().toISOString()
                        }, { onConflict: 'school_id,parent_id' })

                    if (upsertError) {
                        results.errors.push(`Upsert Error (${school.name}, Parent ${parent.parent_id}): ${upsertError.message}`);
                        continue;
                    }
                    results.parents_upserted++
                }

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
