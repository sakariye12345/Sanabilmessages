export function isAuthorizedInternalRequest(
    request: Request,
    secretName = 'INTERNAL_CRON_SECRET',
) {
    const expected = Deno.env.get(secretName) || ''
    const provided = request.headers.get('x-internal-secret') || ''

    if (!expected || expected.length !== provided.length) return false

    let difference = 0
    for (let index = 0; index < expected.length; index += 1) {
        difference |= expected.charCodeAt(index) ^ provided.charCodeAt(index)
    }

    return difference === 0
}
