import os
import asyncio
from supabase import create_client, Client
from datetime import datetime, timedelta

# Hardcoded Service Key from otp_sender.py
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_duplicates():
    print("Checking for duplicates in the last 24 hours...")
    
    # Fetch recent messages
    # Limit to 100 to see recent activity
    response = supabase.table('message_recipients').select(
        'id, created_at, parent_phone, messages(title, body)'
    ).order('created_at', desc=True).limit(100).execute()
    
    data = response.data
    
    seen_bodies = {}
    duplicates = []
    
    print(f"Fetched {len(data)} rows.")
    
    for row in data:
        # Check uniqueness by Content + Phone
        phone = row['parent_phone']
        body = row['messages']['body'] if row['messages'] else 'N/A'
        title = row['messages']['title'] if row['messages'] else 'N/A'
        
        # Key: Phone + Title + Body (First 40 chars)
        sig = f"{phone}|{title}|{body[:40]}"
        
        if sig in seen_bodies:
            prev = seen_bodies[sig]
            duplicates.append((row, prev))
        else:
            seen_bodies[sig] = row
            
    print(f"Total Duplicate Pairs Found: {len(duplicates)}")
    for d1, d2 in duplicates:
        print(f"--- Dup Found ---")
        print(f"ID 1: {d1['id']} at {d1['created_at']}")
        print(f"ID 2: {d2['id']} at {d2['created_at']}")
        print(f"Body: {d1['messages']['body']}")

if __name__ == "__main__":
    check_duplicates()
