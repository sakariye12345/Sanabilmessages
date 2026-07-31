import { isAuthorizedInternalRequest } from '../_shared/internal.ts'

const jsonHeaders = { 'Content-Type': 'application/json' }

Deno.serve((request) => {
    if (!isAuthorizedInternalRequest(request, 'NOTIFY_WEBHOOK_SECRET')) {
        return new Response(JSON.stringify({ error: 'Unauthorized' }), {
            status: 401,
            headers: jsonHeaders,
        })
    }

    // bridge-sync is the only push dispatcher. Database webhooks may continue
    // calling this endpoint without creating duplicate device notifications.
    return new Response(JSON.stringify({
        sent: 0,
        disabled: true,
        dispatcher: 'bridge-sync',
    }), {
        status: 202,
        headers: jsonHeaders,
    })
})
