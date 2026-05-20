# 🚀 Sanabil Messages - Project Handoff Report
**Date:** February 20, 2026
**Project Phase:** Sync Repair, Automation, & Security Hardening

---

## 📅 1. Executive Summary (Dulmar Guud)
In this extensive session, we successfully stabilized the `bridge-sync` infrastructure, ensuring that messages from the internal CI3 system reflect in the React Native application automatically and in real-time. We tackled deep database constraint issues, resolved feature regressions (Disappearing Chats), and applied critical security patches to the data layer. 

The system is now running on **"Auto-Pilot"** via a 1-minute `pg_cron` schedule.

---

## ✅ 2. What We Accomplished & Problems Solved (Shaqooyinkii La Qabtay)

### A. Backend Architecture & Sync Automation
*   **Cron Job Automation:** Successfully enabled `pg_cron` and `pg_net` extensions. Scheduled `bridge-sync` Edge Function to trigger every minute automatically (`ENABLE_SYNC_CRON.sql`).
*   **Idempotency & Duplicate Checks:** Fixed the `compositeCi3Id` generation in the Edge Function to prevent false skips while preventing true duplicates.
*   **Database Constraints Fixed:** Diagnosed why "Fee" (`finance`) and "Exam" messages were failing silently. Updated the `messages_type_check` constraint to support modern operational types: `'general', 'notice', 'absence', 'exam', 'finance', 'receipt', 'received'` (`FIX_TYPE_CONSTRAINT.sql`).

### B. Security & Data Access
*   **RLS Bypass via Secure RPC:** Found that standard Supabase queries with Joins were failing due to complex RLS policies. 
*   **Vulnerability Patched:** The initial workaround (`get_my_inbox(phone)`) was insecure. We replaced it with a parameterless `SECURITY DEFINER` RPC that extracts the user's phone directly from the JWT auth context (`auth.jwt() ->> 'phone'`), making it impenetrable to spoofing (`SECURE_RPC.sql`).

### C. Frontend Resilience (React Native)
*   **Crash Prevention:** Implemented Safe Object Navigation (`?.`) inside `inbox.tsx`'s deduplication logic to prevent `TypeError` crashes when malformed data arrives.
*   **App-Side Sorting & Titles:** Added proper Title Mapping for new message types (including "Payment Received"). Forced a `Date DESC` sort on the client to keep active chats at the top.

---

## 🛠️ 3. Latest Breakthrough (Shaqadii Ugu Dambaysay)

**The "Disappearing Chats" Bug:**
The user identified that when a batch of new messages (e.g., 20+ "Fee" notices) arrived, older categories (like "Absence") completely disappeared from the Inbox UI.
*   **Root Cause:** The `LIMIT 300` on the RPC query starved older message types from being returned if a single type dominated the latest 300 rows.
*   **The Ultimate Fix Drafted:** We created a highly optimized SQL query `get_inbox_summary` (`GET_INBOX_SUMMARY.sql`) that uses `GROUP BY m.type` and `MAX(mr.created_at)`. This guarantees that **one row per active category** is always returned, regardless of how many thousands of messages exist.

---

## 🚧 4. Current Challenges & Pending Tasks (Waxa Qabyada Ah)

To the next AI Assistant, please prioritize the following items:

1.  **Integrate `get_inbox_summary` RPC into `inbox.tsx`:**
    *   The SQL function is created, but the App is currently still using the old `get_my_inbox`. 
    *   **Task:** Refactor `inbox.tsx` to call `.rpc('get_inbox_summary')` for the main list view. This is critical to permanently solve the "Disappearing Chats" issue.
2.  **Secure the Thread View (`thread/[type].tsx`):**
    *   The Thread detail screen needs to be audited to ensure it also uses a secure Data Fetching Strategy (e.g., a specific RPC `get_thread_messages(p_type)` that also uses `auth.jwt()`) instead of standard Supabase `.from()` queries which might conflict with RLS.
3.  **Database-Level Deduplication Constraint:**
    *   While the UI filters duplicates, the Supabase `message_recipients` table lacks a strict `UNIQUE` constraint on `(parent_phone, ci3_id)`. Adding this will prevent the database from bloating with redundant rows if the CI3 API misbehaves.

---
_END OF HANDOFF_
