const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '../.env' });

const app = express();
app.use(express.json());

const PORT = Number(process.env.PORT || 4000);
const POLL_INTERVAL_MS = Number(process.env.WHATSAPP_POLL_INTERVAL_MS || 3000);
const CLAIM_BATCH_SIZE = Number(process.env.WHATSAPP_BATCH_SIZE || 10);
const SERVER_NODE_ID = process.env.WA_SERVER_NODE_ID || 'VPS-1';

const supabaseUrl = process.env.EXPO_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseKey) {
    throw new Error('Missing EXPO_PUBLIC_SUPABASE_URL or SUPABASE_SERVICE_ROLE_KEY');
}

const supabase = createClient(supabaseUrl, supabaseKey);

const clients = new Map();
const qrCodes = new Map();
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

async function logOtp(item, school, status, message, errorMessage = null) {
    try {
        await supabase.from('otp_logs').insert({
            school_id: item.school_id,
            phone: item.phone,
            status,
            provider: 'whatsapp',
            message,
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
        .select('id, name, wa_session_status, server_node_id')
        .in('id', uniqueIds);

    if (error) {
        throw error;
    }

    return new Map((data || []).map((school) => [String(school.id), school]));
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

async function markQueueSent(item, message) {
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

    await logOtp(item, null, 'SENT', message);
}

async function markQueueFailed(item, message, errorMessage) {
    await supabase
        .from('otp_queue')
        .update({
            status: 'FAILED',
            error_message: errorMessage,
            updated_at: new Date().toISOString(),
            provider: 'whatsapp',
        })
        .eq('id', item.id);

    await logOtp(item, null, 'FAILED', message, errorMessage);
}

async function processQueue() {
    try {
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
            const school = schoolMap.get(schoolId);
            const client = clients.get(schoolId);

            if (!school || !client || qrCodes.has(schoolId)) {
                continue;
            }

            const claimedItem = await claimQueueItem(item);
            if (!claimedItem) {
                continue;
            }

            const whatsappNumber = `${claimedItem.phone}@c.us`;
            const message = buildOtpMessage(school, claimedItem.code);

            try {
                await client.sendMessage(whatsappNumber, message);
                console.log(`[WA ${schoolId}] OTP sent to ${claimedItem.phone}`);
                await markQueueSent(claimedItem, message);
            } catch (error) {
                console.error(`[WA ${schoolId}] Send failed for ${claimedItem.phone}:`, error.message);
                await markQueueFailed(claimedItem, message, error.message);
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
        .eq('is_active', true);

    if (error) {
        console.error('[STARTUP] Failed to fetch schools:', error.message);
        return;
    }

    for (const school of schools || []) {
        if (school.server_node_id && school.server_node_id !== SERVER_NODE_ID) {
            continue;
        }
        initSchoolClient(String(school.id));
    }
}

app.get('/health', (_req, res) => {
    res.json({
        ok: true,
        node: SERVER_NODE_ID,
        connectedSchools: [...clients.keys()],
    });
});

app.get('/api/wa/status/:school_id', async (req, res) => {
    const schoolId = String(req.params.school_id);
    const clientExists = clients.has(schoolId);

    if (qrCodes.has(schoolId)) {
        return res.json({ status: 'WAITING_QR', qr: qrCodes.get(schoolId) });
    }

    if (clientExists) {
        return res.json({ status: 'CONNECTED' });
    }

    const { data: school } = await supabase
        .from('schools')
        .select('wa_session_status, server_node_id')
        .eq('id', schoolId)
        .maybeSingle();

    return res.json({
        status: school?.wa_session_status || 'DISCONNECTED',
        node: school?.server_node_id || null,
    });
});

app.post('/api/wa/start/:school_id', async (req, res) => {
    const schoolId = String(req.params.school_id);
    initSchoolClient(schoolId);
    await supabase
        .from('schools')
        .update({ server_node_id: SERVER_NODE_ID })
        .eq('id', schoolId);
    res.json({ success: true, school_id: schoolId, message: 'WhatsApp session start requested.' });
});

app.post('/api/wa/stop/:school_id', async (req, res) => {
    const schoolId = String(req.params.school_id);
    const client = clients.get(schoolId);

    if (client) {
        await client.destroy().catch(() => null);
        clients.delete(schoolId);
        qrCodes.delete(schoolId);
    }

    await updateSchoolStatus(schoolId, 'DISCONNECTED');
    res.json({ success: true, school_id: schoolId, message: 'WhatsApp session stopped.' });
});

app.listen(PORT, async () => {
    console.log(`[SERVER] WhatsApp OTP service listening on ${PORT}`);
    await startAllConnectedSchools();
    setInterval(processQueue, POLL_INTERVAL_MS);
});
