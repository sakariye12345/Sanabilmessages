import os
from dotenv import load_dotenv
from supabase import create_client

load_dotenv()

SUPABASE_URL = os.getenv("EXPO_PUBLIC_SUPABASE_URL")
SUPABASE_KEY = os.getenv("SUPABASE_SERVICE_ROLE_KEY") or os.getenv("EXPO_PUBLIC_SUPABASE_ANON_KEY")
supabase = create_client(SUPABASE_URL, SUPABASE_KEY)

try:
    code = "123456"
    test_phone = "+252634370911" # User's test phone from earlier SQLs
    
    # Check if school_id exists in otp_queue by trying to insert it
    data = {
        "phone": test_phone,
        "code": code,
        "status": "PENDING",
        "school_id": 1 # Let's assume school_id 1
    }
    print("Attempting to insert into otp_queue...")
    result = supabase.table("otp_queue").insert(data).execute()
    print("Success. Inserted OTP:", result.data)
except Exception as e:
    print("Error:", str(e))
