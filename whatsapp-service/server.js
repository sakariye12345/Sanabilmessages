const path = require('path');
const crypto = require('crypto');
const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const WebSocket = require('ws');
const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: path.resolve(__dirname, '.env') });
require('dotenv').config({ path: path.resolve(__dirname, '../.env') });

const app = express();
app.disable('x-powered-by');

const PORT = Number(process.env.PORT || 4000);
const HOST = process.env.HOST || '127.0.0.1';
const POLL_INTERVAL_MS = Number(process.env.WHATSAPP_POLL_INTERVAL_MS || 3000);
const CLAIM_BATCH_SIZE = Number(process.env.WHATSAPP_BATCH_SIZE || 10);
const SERVER_NODE_ID = process.env.WA_SERVER_NODE_ID || 'VPS-1';
const MAX_ATTEMPTS = Number(process.env.WHATSAPP_MAX_ATTEMPTS || 3);
const STALE_PROCESSING_MINUTES = Number(process.env.WHATSAPP_STALE_PROCESSING_MINUTES || 5);
const FAILURE_AUTOPAUSE_THRESHOLD = Number(process.env.WHATSAPP_FAILURE_AUTOPAUSE_THRESHOLD || 5);
const OPERATOR_USERNAME = String(process.env.WA_DASHBOARD_USERNAME || '').trim();
const OPERATOR_PASSWORD = String(process.env.WA_DASHBOARD_PASSWORD || '');
const OPERATOR_RATE_LIMIT = Number(process.env.WA_DASHBOARD_RATE_LIMIT || 120);
const OPERATOR_RATE_WINDOW_MS = Number(process.env.WA_DASHBOARD_RATE_WINDOW_MS || 60000);
const TRUST_PROXY_HOPS = Number(process.env.TRUST_PROXY_HOPS || 0);

const supabaseUrl = process.env.EXPO_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseKey) {
    throw new Error('Missing EXPO_PUBLIC_SUPABASE_URL or SUPABASE_SERVICE_ROLE_KEY');
}

if (!OPERATOR_USERNAME || OPERATOR_PASSWORD.length < 16) {
    throw new Error('WA_DASHBOARD_USERNAME and a WA_DASHBOARD_PASSWORD of at least 16 characters are required');
}

if (!/^[A-Za-z0-9_-]{1,64}$/.test(SERVER_NODE_ID)) {
    throw new Error('WA_SERVER_NODE_ID must contain only letters, numbers, underscores, or hyphens');
}

if (!Number.isFinite(OPERATOR_RATE_LIMIT) || OPERATOR_RATE_LIMIT < 10) {
    throw new Error('WA_DASHBOARD_RATE_LIMIT must be at least 10');
}

if (!Number.isFinite(OPERATOR_RATE_WINDOW_MS) || OPERATOR_RATE_WINDOW_MS < 1000) {
    throw new Error('WA_DASHBOARD_RATE_WINDOW_MS must be at least 1000');
}

if (TRUST_PROXY_HOPS > 0) {
    app.set('trust proxy', TRUST_PROXY_HOPS);
}

app.use(express.json({ limit: '16kb' }));
app.use((_req, res, next) => {
    res.set({
        'Cache-Control': 'no-store',
        'Content-Security-Policy': "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'none'",
        'Referrer-Policy': 'no-referrer',
        'X-Content-Type-Options': 'nosniff',
        'X-Frame-Options': 'DENY',
    });
    next();
});

const operatorRequests = new Map();

function secureEqual(left, right) {
    const leftHash = crypto.createHash('sha256').update(String(left)).digest();
    const rightHash = crypto.createHash('sha256').update(String(right)).digest();
    return crypto.timingSafeEqual(leftHash, rightHash);
}

function authenticateOperator(req, res, next) {
    const now = Date.now();
    const clientKey = req.ip || req.socket.remoteAddress || 'unknown';
    const current = operatorRequests.get(clientKey);
    const bucket = !current || now - current.startedAt >= OPERATOR_RATE_WINDOW_MS
        ? { startedAt: now, count: 0 }
        : current;
    bucket.count += 1;
    operatorRequests.set(clientKey, bucket);

    if (operatorRequests.size > 1000) {
        for (const [key, value] of operatorRequests.entries()) {
            if (now - value.startedAt >= OPERATOR_RATE_WINDOW_MS) {
                operatorRequests.delete(key);
            }
        }
    }

    if (bucket.count > OPERATOR_RATE_LIMIT) {
        return res.status(429).json({ error: 'Too many operator requests. Try again shortly.' });
    }

    const authorization = req.get('authorization') || '';
    if (authorization.startsWith('Basic ')) {
        try {
            const decoded = Buffer.from(authorization.slice(6), 'base64').toString('utf8');
            const separator = decoded.indexOf(':');
            const username = separator >= 0 ? decoded.slice(0, separator) : '';
            const password = separator >= 0 ? decoded.slice(separator + 1) : '';
            if (
                secureEqual(username, OPERATOR_USERNAME) &&
                secureEqual(password, OPERATOR_PASSWORD)
            ) {
                return next();
            }
        } catch {
            // Invalid Basic authorization falls through to the 401 response.
        }
    }

    res.set('WWW-Authenticate', 'Basic realm="Sanabil WhatsApp OTP", charset="UTF-8"');
    return res.status(401).json({ error: 'Operator authentication required.' });
}

app.use((req, res, next) => {
    if (req.path === '/health') return next();
    if (req.path === '/' || req.path.startsWith('/api/wa/')) {
        return authenticateOperator(req, res, next);
    }
    return res.status(404).json({ error: 'Not found.' });
});

const supabase = createClient(supabaseUrl, supabaseKey, {
    realtime: {
        transport: WebSocket,
    },
});

const clients = new Map();
const qrCodes = new Map();
const asyncRoute = (handler) => (req, res, next) => {
    Promise.resolve(handler(req, res, next)).catch(next);
};

function readSchoolId(req, res) {
    const schoolId = String(req.params.school_id || '').trim();
    if (!/^[1-9]\d*$/.test(schoolId)) {
        res.status(400).json({ error: 'school_id must be a positive integer.' });
        return null;
    }
    return schoolId;
}

async function requireOwnedSchool(schoolId, res) {
    const { data: school, error } = await supabase
        .from('schools')
        .select('id, is_active, server_node_id')
        .eq('id', schoolId)
        .maybeSingle();

    if (error) throw error;
    if (!school || !school.is_active) {
        res.status(404).json({ error: 'Active school not found.' });
        return null;
    }
    if (school.server_node_id !== SERVER_NODE_ID) {
        res.status(409).json({
            error: `School is assigned to node ${school.server_node_id || 'UNASSIGNED'}.`,
        });
        return null;
    }
    return school;
}

const PUPPETEER_OPTIONS = {
    headless: true,
    args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--single-process',
        '--disable-gpu',
    ],
};

function buildOtpMessage(school, code) {
    const schoolName = school?.name || 'School';
    return [
        `*${schoolName}*`,
        '',
        `Koodhkaaga gelitaanka waa: *${code}*`,
        '',
        'Koodhkani wuxuu ansax yahay waqti kooban.',
        'Fadlan ha la wadaagin qof kale.',
    ].join('\n');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderDashboardPage() {
    return `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sanabil WhatsApp OTP</title>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #111c35;
            --panel-2: #162443;
            --border: #2b3a5b;
            --text: #e5eefc;
            --muted: #9fb2d4;
            --ok: #22c55e;
            --warn: #f59e0b;
            --bad: #ef4444;
            --accent: #38bdf8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top, #18284c 0%, var(--bg) 55%);
            color: var(--text);
        }
        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }
        .hero {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0;
            font-size: 32px;
        }
        .muted { color: var(--muted); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }
        .card {
            background: linear-gradient(180deg, var(--panel) 0%, var(--panel-2) 100%);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.18);
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .02em;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.04);
        }
        .badge.ok { color: #b7f7cb; border-color: rgba(34,197,94,.35); }
        .badge.warn { color: #fde4ae; border-color: rgba(245,158,11,.35); }
        .badge.bad { color: #fecaca; border-color: rgba(239,68,68,.35); }
        .meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }
        .meta div {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 10px 12px;
        }
        .label {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 4px;
        }
        .value {
            font-size: 15px;
            font-weight: 600;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        button, a.button {
            appearance: none;
            border: 0;
            border-radius: 12px;
            padding: 10px 14px;
            background: var(--accent);
            color: #082032;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        button.secondary, a.button.secondary {
            background: rgba(255,255,255,0.08);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .qr-wrap {
            display: none;
            margin-top: 16px;
            background: rgba(255,255,255,0.03);
            border: 1px dashed rgba(255,255,255,0.15);
            border-radius: 16px;
            padding: 14px;
        }
        .qr-wrap.active { display: block; }
        .qr-wrap img {
            width: min(100%, 320px);
            background: #fff;
            border-radius: 12px;
            padding: 10px;
        }
        .small { font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <div>
                <h1>Sanabil WhatsApp OTP</h1>
                <div class="muted">Dashboard-kan wuxuu tusayaa school sessions, queue status, iyo QR scan-ka login-ka WhatsApp.</div>
            </div>
            <div class="badge" id="node-badge">Loading...</div>
        </div>
        <div class="grid" id="school-grid"></div>
    </div>
    <script>
        const BASE_PATH = window.location.pathname.startsWith('/otp-whatsapp') ? '/otp-whatsapp' : '';

        async function callApi(url, method = 'GET', body) {
            const options = { method, headers: { 'Content-Type': 'application/json' } };
            if (body) options.body = JSON.stringify(body);
            const res = await fetch(BASE_PATH + url, options);
            if (!res.ok) throw new Error('Request failed');
            return res.json();
        }

        function badgeClass(status, waitingQr, paused) {
            if (waitingQr) return 'warn';
            if (paused) return 'bad';
            if (status === 'CONNECTED') return 'ok';
            return 'warn';
        }

        function statusText(status, waitingQr, paused) {
            if (paused) return 'PAUSED';
            if (waitingQr) return 'WAITING_QR';
            return status || 'DISCONNECTED';
        }

        function queueValue(queue, key) {
            return queue && typeof queue[key] !== 'undefined' ? queue[key] : 0;
        }

        function renderSchoolCard(school) {
            const status = statusText(school.wa_session_status, school.waitingQr, school.otp_is_paused);
            const badge = badgeClass(school.wa_session_status, school.waitingQr, school.otp_is_paused);
            return \`
                <div class="card" id="school-\${school.id}">
                    <div class="row">
                        <div>
                            <div class="small muted">School #\${school.id}</div>
                            <div style="font-size:22px;font-weight:700;">\${school.name}</div>
                        </div>
                        <div class="badge \${badge}">\${status}</div>
                    </div>
                    <div class="meta">
                        <div><div class="label">Queue PENDING</div><div class="value">\${queueValue(school.queue, 'PENDING')}</div></div>
                        <div><div class="label">Queue SENT</div><div class="value">\${queueValue(school.queue, 'SENT')}</div></div>
                        <div><div class="label">Cooldown</div><div class="value">\${school.otp_cooldown_seconds || 0}s</div></div>
                        <div><div class="label">Daily Cap</div><div class="value">\${school.otp_daily_cap || 0}</div></div>
                    </div>
                    <div class="actions">
                        <button onclick="startSchool(\${school.id})">Start Session</button>
                        <button class="secondary" onclick="showQr(\${school.id})">Show QR</button>
                        <button class="secondary" onclick="refreshStatus(\${school.id})">Refresh</button>
                    </div>
                    <div class="qr-wrap" id="qr-wrap-\${school.id}">
                        <div class="small muted" id="qr-note-\${school.id}">QR wali lama helin. Riix Start Session kadibna Show QR.</div>
                        <img id="qr-img-\${school.id}" alt="QR Code" style="display:none;" />
                    </div>
                </div>
            \`;
        }

        async function loadSummary() {
            const data = await callApi('/api/wa/summary');
            document.getElementById('node-badge').textContent = 'Node: ' + (data.node || 'unknown');
            document.getElementById('school-grid').innerHTML = (data.schools || []).map(renderSchoolCard).join('');
        }

        async function startSchool(schoolId) {
            await callApi('/api/wa/start/' + schoolId, 'POST');
            await loadSummary();
            await showQr(schoolId);
        }

        async function refreshStatus(schoolId) {
            await showQr(schoolId, true);
            await loadSummary();
        }

        async function showQr(schoolId, keepOpen = false) {
            const wrap = document.getElementById('qr-wrap-' + schoolId);
            const img = document.getElementById('qr-img-' + schoolId);
            const note = document.getElementById('qr-note-' + schoolId);
            if (!wrap) return;
            wrap.classList.add('active');

            const data = await callApi('/api/wa/status/' + schoolId);
            if (data.qr) {
                img.src = data.qr;
                img.style.display = 'block';
                note.textContent = 'WhatsApp-ka phone-ka ku fur, Linked devices gal, kadib QR-kan scan garee.';
            } else {
                img.style.display = 'none';
                note.textContent = 'Status: ' + (data.status || 'UNKNOWN') + '. Haddii uu CONNECTED yahay QR looma baahna.';
            }

            if (!keepOpen) {
                setTimeout(() => showQr(schoolId, true).catch(() => null), 5000);
            }
        }

        loadSummary().catch((error) => {
            document.getElementById('school-grid').innerHTML = '<div class="card">Dashboard load failed: ' + error.message + '</div>';
        });
        setInterval(() => loadSummary().catch(() => null), 15000);
    </script>
</body>
</html>`;
}

async function logOtp(item, status, errorMessage = null) {
    try {
        const eventMessage = status === 'SENT'
            ? 'OTP delivered through WhatsApp.'
            : 'OTP delivery failed.';
        await supabase.from('otp_logs').insert({
            school_id: item.school_id,
            phone: item.phone,
            status,
            provider: 'whatsapp',
            message: eventMessage,
            error_message: errorMessage,
            sent_at: status === 'SENT' ? new Date().toISOString() : null,
        });
    } catch (error) {
        console.error('[OTP LOG] Failed:', error.message);
    }
}

async function updateSchoolStatus(schoolId, status) {
    await supabase
        .from('schools')
        .update({ wa_session_status: status, server_node_id: SERVER_NODE_ID })
        .eq('id', schoolId);
}

async function updateSchoolOtpState(schoolId, values) {
    await supabase
        .from('schools')
        .update(values)
        .eq('id', schoolId);
}

function initSchoolClient(schoolId) {
    if (clients.has(schoolId)) return clients.get(schoolId);

    console.log(`[WA ${schoolId}] Starting session...`);

    const client = new Client({
        authStrategy: new LocalAuth({ clientId: `school_${schoolId}` }),
        puppeteer: PUPPETEER_OPTIONS,
    });

    client.on('qr', async (qr) => {
        console.log(`[WA ${schoolId}] QR generated.`);
        qrcode.toDataURL(qr, (err, url) => {
            if (!err) qrCodes.set(schoolId, url);
        });
        updateSchoolStatus(schoolId, 'WAITING_QR').catch((error) => {
            console.error(`[WA ${schoolId}] Failed to store WAITING_QR:`, error.message);
        });
    });

    client.on('authenticated', () => {
        console.log(`[WA ${schoolId}] Authenticated.`);
    });

    client.on('ready', async () => {
        console.log(`[WA ${schoolId}] Connected.`);
        qrCodes.delete(schoolId);
        await updateSchoolStatus(schoolId, 'CONNECTED');
    });

    client.on('auth_failure', async (message) => {
        console.error(`[WA ${schoolId}] Auth failure:`, message);
        qrCodes.delete(schoolId);
        clients.delete(schoolId);
        await updateSchoolStatus(schoolId, 'DISCONNECTED');
    });

    client.on('disconnected', async (reason) => {
        console.log(`[WA ${schoolId}] Disconnected:`, reason);
        qrCodes.delete(schoolId);
        clients.delete(schoolId);
        await updateSchoolStatus(schoolId, 'DISCONNECTED');
    });

    client.initialize().catch((error) => {
        console.error(`[WA ${schoolId}] Initialize error:`, error.message);
    });

    clients.set(schoolId, client);
    return client;
}

async function getSchoolMap(schoolIds) {
    const uniqueIds = [...new Set(schoolIds.filter(Boolean))];
    if (uniqueIds.length === 0) return new Map();

    const { data, error } = await supabase
        .from('schools')
        .select('id, name, wa_session_status, server_node_id, otp_is_paused, otp_pause_reason, otp_pause_until, otp_cooldown_seconds, otp_daily_cap, otp_last_sent_at, otp_consecutive_failures')
        .in('id', uniqueIds);

    if (error) {
        throw error;
    }

    return new Map((data || []).map((school) => [String(school.id), school]));
}

function isSchoolPaused(school) {
    if (!school) return true;
    if (school.otp_is_paused) {
        if (!school.otp_pause_until) return true;
        return new Date(school.otp_pause_until).getTime() > Date.now();
    }
    return false;
}

async function normalizeExpiredPause(schoolId, school) {
    if (!school?.otp_is_paused || !school?.otp_pause_until) {
        return school;
    }

    if (new Date(school.otp_pause_until).getTime() > Date.now()) {
        return school;
    }

    await updateSchoolOtpState(schoolId, {
        otp_is_paused: false,
        otp_pause_reason: null,
        otp_pause_until: null,
    });

    return {
        ...school,
        otp_is_paused: false,
        otp_pause_reason: null,
        otp_pause_until: null,
    };
}

function hasCooldown(school) {
    if (!school?.otp_last_sent_at) return false;
    const cooldownSeconds = Number(school.otp_cooldown_seconds || 0);
    if (cooldownSeconds <= 0) return false;

    const nextAllowed = new Date(school.otp_last_sent_at).getTime() + cooldownSeconds * 1000;
    return Date.now() < nextAllowed;
}

async function hasReachedDailyCap(schoolId, school) {
    const dailyCap = Number(school?.otp_daily_cap || 0);
    if (dailyCap <= 0) return false;

    const todayStart = new Date();
    todayStart.setUTCHours(0, 0, 0, 0);

    const { count, error } = await supabase
        .from('otp_logs')
        .select('*', { count: 'exact', head: true })
        .eq('school_id', Number(schoolId))
        .eq('status', 'SENT')
        .gte('sent_at', todayStart.toISOString());

    if (error) throw error;
    return (count || 0) >= dailyCap;
}

async function recoverStaleProcessingRows() {
    const threshold = new Date(Date.now() - STALE_PROCESSING_MINUTES * 60 * 1000).toISOString();

    const { data: staleItems, error } = await supabase
        .from('otp_queue')
        .select('id, attempt_count')
        .eq('status', 'PROCESSING')
        .lt('processing_started_at', threshold);

    if (error) throw error;
    if (!staleItems?.length) return;

    for (const item of staleItems) {
        const shouldFail = Number(item.attempt_count || 0) >= MAX_ATTEMPTS;
        await supabase
            .from('otp_queue')
            .update({
                status: shouldFail ? 'FAILED' : 'PENDING',
                error_message: shouldFail ? 'OTP processing stale timeout reached.' : 'OTP processing recovered after stale timeout.',
                updated_at: new Date().toISOString(),
            })
            .eq('id', item.id)
            .eq('status', 'PROCESSING');
    }
}

async function markQueueSuperseded(item) {
    await supabase
        .from('otp_queue')
        .update({
            status: 'FAILED',
            error_message: 'Superseded by newer OTP request.',
            updated_at: new Date().toISOString(),
            provider: 'whatsapp',
        })
        .eq('id', item.id);
}

async function hasNewerPendingOtp(item) {
    const { count, error } = await supabase
        .from('otp_queue')
        .select('*', { count: 'exact', head: true })
        .eq('school_id', item.school_id)
        .eq('phone', item.phone)
        .eq('status', 'PENDING')
        .gt('created_at', item.created_at);

    if (error) throw error;
    return (count || 0) > 0;
}

async function claimQueueItem(item) {
    const nextAttemptCount = (item.attempt_count || 0) + 1;
    const { data, error } = await supabase
        .from('otp_queue')
        .update({
            status: 'PROCESSING',
            attempt_count: nextAttemptCount,
            processing_started_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            error_message: null,
            provider: 'whatsapp',
        })
        .eq('id', item.id)
        .eq('status', 'PENDING')
        .select('id, phone, code, school_id, attempt_count')
        .maybeSingle();

    if (error) throw error;
    return data;
}

async function markQueueSent(item) {
    const now = new Date().toISOString();
    await supabase
        .from('otp_queue')
        .update({
            status: 'SENT',
            sent_at: now,
            updated_at: now,
            error_message: null,
            provider: 'whatsapp',
        })
        .eq('id', item.id);

    await updateSchoolOtpState(String(item.school_id), {
        otp_last_sent_at: now,
        otp_last_error_at: null,
        otp_consecutive_failures: 0,
        otp_is_paused: false,
        otp_pause_reason: null,
        otp_pause_until: null,
    });

    await logOtp(item, 'SENT');
}

async function markQueueFailed(item, errorMessage) {
    const schoolId = String(item.school_id || '');
    let nextFailureCount = Number(item.school_failures || 0) + 1;

    await supabase
        .from('otp_queue')
        .update({
            status: 'FAILED',
            error_message: errorMessage,
            updated_at: new Date().toISOString(),
            provider: 'whatsapp',
        })
        .eq('id', item.id);

    const schoolPatch = {
        otp_last_error_at: new Date().toISOString(),
        otp_consecutive_failures: nextFailureCount,
    };

    if (nextFailureCount >= FAILURE_AUTOPAUSE_THRESHOLD) {
        schoolPatch.otp_is_paused = true;
        schoolPatch.otp_pause_reason = `Auto-paused after ${nextFailureCount} consecutive OTP failures.`;
        schoolPatch.otp_pause_until = new Date(Date.now() + 15 * 60 * 1000).toISOString();
    }

    await updateSchoolOtpState(schoolId, schoolPatch);
    await logOtp(item, 'FAILED', errorMessage);
}

async function processQueue() {
    try {
        await recoverStaleProcessingRows();

        const { data: queue, error } = await supabase
            .from('otp_queue')
            .select('id, phone, code, school_id, status, attempt_count, created_at')
            .eq('status', 'PENDING')
            .order('created_at', { ascending: true })
            .limit(CLAIM_BATCH_SIZE);

        if (error) throw error;
        if (!queue || queue.length === 0) return;

        const schoolMap = await getSchoolMap(queue.map((item) => item.school_id));

        for (const item of queue) {
            const schoolId = String(item.school_id || '');
            let school = schoolMap.get(schoolId);
            const client = clients.get(schoolId);

            if (
                !school ||
                school.server_node_id !== SERVER_NODE_ID ||
                !client ||
                qrCodes.has(schoolId)
            ) {
                continue;
            }

            school = await normalizeExpiredPause(schoolId, school);

            if (isSchoolPaused(school)) {
                continue;
            }

            if (hasCooldown(school)) {
                continue;
            }

            if (await hasReachedDailyCap(schoolId, school)) {
                await updateSchoolOtpState(schoolId, {
                    otp_is_paused: true,
                    otp_pause_reason: 'Daily OTP cap reached.',
                    otp_pause_until: new Date(Date.now() + 60 * 60 * 1000).toISOString(),
                });
                continue;
            }

            if (await hasNewerPendingOtp(item)) {
                await markQueueSuperseded(item);
                continue;
            }

            const claimedItem = await claimQueueItem(item);
            if (!claimedItem) {
                continue;
            }

            if (Number(claimedItem.attempt_count || 0) > MAX_ATTEMPTS) {
                await markQueueFailed(
                    { ...claimedItem, school_failures: school.otp_consecutive_failures },
                    'OTP max retry limit reached.'
                );
                continue;
            }

            const whatsappNumber = `${claimedItem.phone}@c.us`;
            const message = buildOtpMessage(school, claimedItem.code);

            try {
                await client.sendMessage(whatsappNumber, message);
                console.log(`[WA ${schoolId}] OTP sent to ${claimedItem.phone}`);
                await markQueueSent(claimedItem);
            } catch (error) {
                console.error(`[WA ${schoolId}] Send failed for ${claimedItem.phone}:`, error.message);
                await markQueueFailed(
                    { ...claimedItem, school_failures: school.otp_consecutive_failures },
                    error.message
                );
            }
        }
    } catch (error) {
        console.error('[QUEUE] Processing error:', error.message);
    }
}

async function startAllConnectedSchools() {
    const { data: schools, error } = await supabase
        .from('schools')
        .select('id, wa_session_status, server_node_id')
        .in('wa_session_status', ['CONNECTED', 'WAITING_QR'])
        .eq('is_active', true)
        .eq('server_node_id', SERVER_NODE_ID);

    if (error) {
        console.error('[STARTUP] Failed to fetch schools:', error.message);
        return;
    }

    for (const school of schools || []) {
        initSchoolClient(String(school.id));
    }
}

async function getQueueSummary() {
    const { data, error } = await supabase
        .from('otp_queue')
        .select('school_id, status');

    if (error) throw error;

    const summary = {};
    for (const row of data || []) {
        const schoolId = String(row.school_id || 'unknown');
        if (!summary[schoolId]) {
            summary[schoolId] = { PENDING: 0, PROCESSING: 0, SENT: 0, FAILED: 0 };
        }
        const status = row.status || 'PENDING';
        if (summary[schoolId][status] === undefined) {
            summary[schoolId][status] = 0;
        }
        summary[schoolId][status] += 1;
    }

    return summary;
}

app.get('/health', (_req, res) => {
    res.json({
        ok: true,
        node: SERVER_NODE_ID,
        connectedSchoolCount: clients.size,
    });
});

app.get('/', (_req, res) => {
    res.type('html').send(renderDashboardPage());
});

app.get('/api/wa/status/:school_id', asyncRoute(async (req, res) => {
    const schoolId = readSchoolId(req, res);
    if (!schoolId) return;
    const school = await requireOwnedSchool(schoolId, res);
    if (!school) return;
    const clientExists = clients.has(schoolId);

    if (qrCodes.has(schoolId)) {
        return res.json({ status: 'WAITING_QR', qr: qrCodes.get(schoolId) });
    }

    if (clientExists) {
        return res.json({ status: 'CONNECTED' });
    }

    const { data: statusRow, error } = await supabase
        .from('schools')
        .select('wa_session_status, server_node_id, otp_is_paused, otp_pause_reason, otp_pause_until')
        .eq('id', schoolId)
        .maybeSingle();

    if (error) throw error;
    if (!statusRow) {
        return res.status(404).json({ error: 'School not found.' });
    }

    return res.json({
        status: statusRow.wa_session_status || 'DISCONNECTED',
        node: statusRow.server_node_id || null,
        otpPaused: statusRow.otp_is_paused || false,
        otpPauseReason: statusRow.otp_pause_reason || null,
        otpPauseUntil: statusRow.otp_pause_until || null,
    });
}));

app.get('/api/wa/summary', async (_req, res) => {
    try {
        const { data: schools, error } = await supabase
            .from('schools')
            .select('id, name, wa_session_status, server_node_id, otp_is_paused, otp_pause_reason, otp_pause_until, otp_cooldown_seconds, otp_daily_cap, otp_last_sent_at, otp_last_error_at, otp_consecutive_failures')
            .eq('is_active', true)
            .eq('server_node_id', SERVER_NODE_ID)
            .order('id', { ascending: true });

        if (error) throw error;

        const queueSummary = await getQueueSummary();

        res.json({
            ok: true,
            node: SERVER_NODE_ID,
            schools: (schools || []).map((school) => ({
                ...school,
                queue: queueSummary[String(school.id)] || { PENDING: 0, PROCESSING: 0, SENT: 0, FAILED: 0 },
                clientConnected: clients.has(String(school.id)),
                waitingQr: qrCodes.has(String(school.id)),
            })),
        });
    } catch (error) {
        res.status(500).json({
            ok: false,
            error: error.message,
        });
    }
});

app.post('/api/wa/start/:school_id', asyncRoute(async (req, res) => {
    const schoolId = readSchoolId(req, res);
    if (!schoolId) return;
    const { data: school, error } = await supabase
        .from('schools')
        .update({ server_node_id: SERVER_NODE_ID })
        .eq('id', schoolId)
        .eq('is_active', true)
        .or(`server_node_id.is.null,server_node_id.eq.${SERVER_NODE_ID}`)
        .select('id, is_active, server_node_id')
        .maybeSingle();

    if (error) throw error;
    if (!school || !school.is_active) {
        return res.status(409).json({
            error: 'School does not exist, is inactive, or is assigned to another node.',
        });
    }

    initSchoolClient(schoolId);
    res.json({ success: true, school_id: schoolId, message: 'WhatsApp session start requested.' });
}));

app.post('/api/wa/stop/:school_id', asyncRoute(async (req, res) => {
    const schoolId = readSchoolId(req, res);
    if (!schoolId) return;
    const school = await requireOwnedSchool(schoolId, res);
    if (!school) return;
    const client = clients.get(schoolId);

    if (client) {
        await client.destroy().catch(() => null);
        clients.delete(schoolId);
        qrCodes.delete(schoolId);
    }

    await updateSchoolStatus(schoolId, 'DISCONNECTED');
    res.json({ success: true, school_id: schoolId, message: 'WhatsApp session stopped.' });
}));

app.post('/api/wa/pause/:school_id', asyncRoute(async (req, res) => {
    const schoolId = readSchoolId(req, res);
    if (!schoolId) return;
    const school = await requireOwnedSchool(schoolId, res);
    if (!school) return;
    const reason = typeof req.body?.reason === 'string' && req.body.reason.trim()
        ? req.body.reason.trim()
        : 'Paused by operator.';
    const pauseMinutes = Number(req.body?.pause_minutes || 0);
    const pauseUntil = pauseMinutes > 0
        ? new Date(Date.now() + pauseMinutes * 60 * 1000).toISOString()
        : null;

    await updateSchoolOtpState(schoolId, {
        otp_is_paused: true,
        otp_pause_reason: reason,
        otp_pause_until: pauseUntil,
    });

    res.json({ success: true, school_id: schoolId, paused: true, reason, pause_until: pauseUntil });
}));

app.post('/api/wa/resume/:school_id', asyncRoute(async (req, res) => {
    const schoolId = readSchoolId(req, res);
    if (!schoolId) return;
    const school = await requireOwnedSchool(schoolId, res);
    if (!school) return;

    await updateSchoolOtpState(schoolId, {
        otp_is_paused: false,
        otp_pause_reason: null,
        otp_pause_until: null,
        otp_consecutive_failures: 0,
    });

    res.json({ success: true, school_id: schoolId, paused: false });
}));

app.use((error, _req, res, _next) => {
    console.error('[HTTP]', error);
    if (!res.headersSent) {
        res.status(500).json({ error: 'Internal server error.' });
    }
});

let queueTimer = null;
const httpServer = app.listen(PORT, HOST, async () => {
    console.log(`[SERVER] WhatsApp OTP service listening on ${HOST}:${PORT}`);
    try {
        await startAllConnectedSchools();
        queueTimer = setInterval(processQueue, POLL_INTERVAL_MS);
    } catch (error) {
        console.error('[STARTUP]', error);
    }
});

async function shutdown(signal) {
    console.log(`[SERVER] ${signal} received; shutting down.`);
    if (queueTimer) clearInterval(queueTimer);
    await Promise.all(
        [...clients.values()].map((client) => client.destroy().catch(() => null)),
    );
    httpServer.close(() => process.exit(0));
    setTimeout(() => process.exit(1), 10000).unref();
}

process.once('SIGTERM', () => shutdown('SIGTERM'));
process.once('SIGINT', () => shutdown('SIGINT'));
