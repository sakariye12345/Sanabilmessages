import time
import json
import os
from supabase import create_client, Client
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

# --- CONFIG ---
# VPS Environment Variables or Hardcoded for now
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY" # SERVICE_ROLE_KEY (Must fill this manually or use env)
SCHOOL_NAME = "Sanabil School"

# --- SUPABASE CLIENT ---
supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def init_driver():
    options = webdriver.ChromeOptions()
    
    # Use absolute path for Windows compatibility
    current_dir = os.path.dirname(os.path.abspath(__file__))
    user_data_dir = os.path.join(current_dir, "whatsapp_session")
    
    options.add_argument(f"--user-data-dir={user_data_dir}") # Persist Login
    options.add_argument("--remote-debugging-port=9222") # Fix DevToolsActivePort error
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    # options.add_argument("--headless") # Headless mode for VPS (enable later)
    
    # Check if folder exists
    if not os.path.exists(user_data_dir):
        print(f"📁 Creating new session folder at: {user_data_dir}")
        os.makedirs(user_data_dir)
        
    try:
        driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)
        driver.get("https://web.whatsapp.com")
        print("Please scan QR code if not logged in...")
        time.sleep(15) # Wait for initial load
        return driver
    except Exception as e:
        print(f"❌ Failed to start Chrome Driver: {e}")
        print("💡 Suggestion: Close all open Chrome windows and try again.")
        raise e

from selenium.webdriver.common.keys import Keys

def send_whatsapp_message(driver, phone, message):
    try:
        clean_phone = phone.replace("+", "")
        print(f"🔍 Searching for {clean_phone}...")

        # 1. Find Search Bar & Search for User
        try:
            # Try multiple selectors for Search Bar (Generic and Specific)
            # data-tab="3" is common, but aria-label is more semantic
            xpath_search = '//div[@contenteditable="true"][@data-tab="3"]'
            
            search_box = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.XPATH, xpath_search))
            )
            
            # Clear previous search if any
            search_box.click()
            # Select all and delete to be safe
            search_box.send_keys(Keys.CONTROL + "a")
            search_box.send_keys(Keys.BACKSPACE)
            
            # Type Phone
            search_box.send_keys(clean_phone)
            time.sleep(1.5) # Wait for search results
            search_box.send_keys(Keys.ENTER) # Select first result
            
        except Exception as e:
            print(f"⚠️ Search failed: {e}. Trying fallback selector...")
            return False

        # 2. Find Message Box & Send
        try:
            print("   Typing message...")
            # data-tab="10" is the standard message input
            xpath_msg_box = '//div[@contenteditable="true"][@data-tab="10"]'
            
            msg_box = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.XPATH, xpath_msg_box))
            )
            
            # Type Message
            # Handle newlines by converting to Shift+Enter if needed, or valid chars
            # Simple send_keys usually works for text.
            msg_box.send_keys(message)
            time.sleep(0.5)
            msg_box.send_keys(Keys.ENTER) # Send
            
            print(f"✅ Sent to {phone} (via UI)")
            time.sleep(1) # Short wait for send tick
            return True

        except Exception as e:
            print(f"❌ Message box not found (Invalid number?): {e}")
            return False

    except Exception as e:
        print(f"❌ Error sending to {phone}: {e}")
        return False

def process_queue(driver):
    print("Checking OTP queue...")
    
    # Fetch PENDING requests
    response = supabase.table("otp_queue").select("*").eq("status", "PENDING").limit(1).execute()
    
    if not response.data:
        return

    item = response.data[0]
    phone = item['phone']
    code = item['code']
    record_id = item['id']
    
    print(f"🔄 Processing OTP for {phone}")
    
    # Standard OTP Format + Deep Link
    # Link format: scheme://path?params
    # Path matches file structure in Expo Router, but groups (auth) are stripped.
    # Correct path: verify
    deep_link = f"sanabilmessages://verify?phone={phone}&code={code}"
    
    message = f"*{code}* is your verification code for {SCHOOL_NAME}.\n\nTap to verify:\n{deep_link}"
    
    success = send_whatsapp_message(driver, phone, message)
    
    if success:
        # Update Status to SENT
        supabase.table("otp_queue").update({"status": "SENT"}).eq("id", record_id).execute()
        
        # Log to History
        supabase.table("otp_logs").insert({
            "phone": phone,
            "message": message,
            "status": "SENT",
            "sent_at": "now()"
        }).execute()
        print(f"✅ OTP Processed for {phone}")
        
    else:
        # 🚨 Handle Failure - Prevent Silent Failure
        print(f"⚠️ Marking as FAILED for {phone}")
        supabase.table("otp_queue").update({
            "status": "FAILED",
            "error_message": "Automation failed (UI changed?)" 
        }).eq("id", record_id).execute()
        
        # Optional: Log failure to history too
        supabase.table("otp_logs").insert({
            "phone": phone,
            "message": message,
            "status": "FAILED",
            "error_message": "Automation failed",
            "sent_at": "now()"
        }).execute()

def main():
    driver = init_driver()
    print("🚀 OTP Sender Service Started...")
    
    while True:
        try:
            process_queue(driver)
            time.sleep(0.5) # Fast Poll
        except Exception as e:
            print(f"⚠️ Loop Error: {e}")
            time.sleep(5)

if __name__ == "__main__":
    main()
