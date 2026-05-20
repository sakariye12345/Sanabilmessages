-- ==========================================
-- 🏢 MULTI-TENANT WHATSAPP BOT SCHEMA UPDATE
-- ==========================================
-- Tani waxay dammaanad qaadaysaa in uusan jabin (Break) nidaamkii hore, laakiin
-- kaliya aynu ku darno labada qaybood ee Maamulka Bot-yada shakhsiga ah ee Iskuulada.

DO $$ 
BEGIN
    -- 1. Status-ka WhatsApp ee Iskuulka (Shidan, Dansan, Dib-u-Skan)
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schools' AND column_name='wa_session_status') THEN
        ALTER TABLE public.schools ADD COLUMN wa_session_status TEXT DEFAULT 'DISCONNECTED';
    END IF;

    -- 2. Diiwaanka VPS-ka xammilaya Bot-kan (Qorshaha Ballaarinta Mustaqbalka)
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schools' AND column_name='server_node_id') THEN
        ALTER TABLE public.schools ADD COLUMN server_node_id TEXT DEFAULT 'VPS-1';
    END IF;
END $$;
