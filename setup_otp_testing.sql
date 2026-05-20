-- ===============================================
-- 🚀 SANABIL MESSAGES - WHATSAPP OTP TESTING
-- ===============================================
-- Maadaama Microservice-ku wado Iskuulo badan (Multi-Tenant) 
-- waxa uu OTP_QUEUE ku dhex raadinayaa "school_id" oo awal table-ka ka maqnaa.

-- 1. Ku dar Column-ka school_id
ALTER TABLE public.otp_queue ADD COLUMN IF NOT EXISTS school_id BIGINT;

-- 2. U fur In aan tijaabo (Insert) ku samayn karno API Public key (ANON KEY)
DROP POLICY IF EXISTS "Allow test anon inserts on queue" ON public.otp_queue;
CREATE POLICY "Allow test anon inserts on queue" 
ON public.otp_queue FOR INSERT TO anon WITH CHECK (true);

-- 3. U fur Select (si service-ku u akhriyo tijaabada)
DROP POLICY IF EXISTS "Allow test anon selects on queue" ON public.otp_queue;
CREATE POLICY "Allow test anon selects on queue" 
ON public.otp_queue FOR SELECT TO anon USING (true);

-- 4. U fur Update (si service-ku usoo celiyo 'SENT')
DROP POLICY IF EXISTS "Allow test anon updates on queue" ON public.otp_queue;
CREATE POLICY "Allow test anon updates on queue" 
ON public.otp_queue FOR UPDATE TO anon USING (true);
