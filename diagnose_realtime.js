const { createClient } = require('@supabase/supabase-js');

// Config
const SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co";
const SUPABASE_SERVICE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY";

const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

async function testRealtime() {
    console.log("--- REALTIME DIAGNOSTIC START ---");
    console.log("Listening for INSERT on 'message_recipients'...");

    const channel = supabase
        .channel('diagnostic-channel')
        .on(
            'postgres_changes',
            {
                event: '*',
                schema: 'public',
                // table: 'message_recipients', // Commented out to hear EVERYTHING
            },
            (payload) => {
                console.log("✅ EVENT RECEIVED:", JSON.stringify(payload.new, null, 2));
            }
        )
        .subscribe((status) => {
            console.log("SUBSCRIPTION STATUS:", status);
        });

    // Keep alive
    setInterval(() => {
        console.log("Waiting for events... (Press Ctrl+C to stop)");
    }, 5000);
}

testRealtime();
