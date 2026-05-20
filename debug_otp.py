import requests
import json
import os

# CONFIG
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
# We need the ANON key to invoke the function (or Service Role, but App uses Anon)
# Let's use the Anon Key from the .env file we saw earlier
ANON_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." # I will need to replace this with the actual key from .env

# I'll read .env to get the key automatically
def get_anon_key():
    try:
        with open(".env", "r") as f:
            for line in f:
                if "EXPO_PUBLIC_SUPABASE_ANON_KEY" in line:
                    return line.split("=")[1].strip()
    except:
        return ""

KEY = get_anon_key()
FUNCTION_URL = f"{SUPABASE_URL}/functions/v1/request-otp"

print(f"Testing Function: {FUNCTION_URL}")

payload = {
    "phone": "+252634370911" # The phone number from the logs
}

headers = {
    "Authorization": f"Bearer {KEY}",
    "Content-Type": "application/json"
}

try:
    response = requests.post(FUNCTION_URL, json=payload, headers=headers)
    
    print(f"Status Code: {response.status_code}")
    print("Response Body:")
    print(response.text)
except Exception as e:
    print(f"Error: {e}")
