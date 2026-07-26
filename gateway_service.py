import time
import requests
import os
import urllib.parse
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from rich.console import Console
from rich.progress import Progress

# ==========================================
# CONFIGURATION
# ==========================================
API_TOKEN = os.environ.get("CI3_API_TOKEN", "").strip()
if not API_TOKEN:
    raise RuntimeError("CI3_API_TOKEN environment variable is required")
ENDPOINT_FETCH = "https://schoolsfls443dr4rsm53m.shihaab.tech/messages/contacts"
ENDPOINT_UPDATE = "https://schoolsfls443dr4rsm53m.shihaab.tech/messages/update_status"

DORMANT_THRESHOLD = 300  # Seconds before going dormant
POLL_INTERVAL = 30       # Seconds to wait when no messages

# Console for styled printing
console = Console()

def make_chrome_options(run_in_background: bool) -> Options:
    """Create Chrome options for either background or on-screen mode."""
    chrome_options = Options()
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_experimental_option('excludeSwitches', ['enable-logging'])
    
    script_directory = os.path.dirname(os.path.abspath(__file__))
    profile_path = os.path.join(script_directory, "chrome_profile")
    
    chrome_options.add_argument(f"--user-data-dir={profile_path}")
    chrome_options.add_argument("--profile-directory=Default")
    
    if run_in_background:
        chrome_options.add_argument("--headless=new")
        
    return chrome_options

class WhatsAppGateway:
    def __init__(self):
        self.driver = None
        self.last_activity_time = time.time()

    def start_driver(self, run_in_background=False):
        """Starts the Chrome driver."""
        mode = "background" if run_in_background else "foreground"
        console.print(f"[blue]Starting WhatsApp Service ({mode})...[/blue]")
        
        service = Service(log_path=os.devnull)
        options = make_chrome_options(run_in_background)
        
        self.driver = webdriver.Chrome(service=service, options=options)
        self.driver.get("https://web.whatsapp.com/")
        time.sleep(5) # Initial load wait

    def stop_driver(self):
        """Stops the driver to save resources."""
        if self.driver:
            console.print("[blue]Stopping Chrome driver...[/blue]")
            try:
                self.driver.quit()
            except:
                pass
            self.driver = None

    def is_session_active(self):
        """Checks if WhatsApp Web is logged in (QR code scanned)."""
        if not self.driver: return False
        try:
            # Look for the chat list pane
            WebDriverWait(self.driver, 15).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "div[role='row']")) # Generic row selector for chat list
            )
            return True
        except:
            return False

    def ensure_session(self):
        """Ensures the session is active, prompting for QR scan if needed."""
        self.start_driver(run_in_background=True)
        
        if not self.is_session_active():
            self.stop_driver()
            console.print("[yellow]Session invalid. Switching to foreground for QR Scan...[/yellow]")
            self.start_driver(run_in_background=False)
            
            console.print("[green]Please scan the QR code now![/green]")
            try:
                # Wait longer for initial scan
                WebDriverWait(self.driver, 60).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, "div[role='row']"))
                )
                console.print("[green]Session Active![/green]")
            except:
                console.print("[red]QR Scan timed out.[/red]")
                return False
                
            # Restart in background mode
            self.stop_driver()
            self.start_driver(run_in_background=True)
            
        return True

    def fetch_messages(self):
        """Fetches pending messages from the API."""
        headers = {"Authorization": API_TOKEN}
        try:
            resp = requests.get(ENDPOINT_FETCH, headers=headers, timeout=10)
            if resp.status_code == 200:
                data = resp.json()
                # Filter out already failed or invalid if API returns them
                return [d for d in data if d.get('sent_status') not in ['sent', 'failed']]
            else:
                console.print(f"[red]API Error: {resp.status_code} - {resp.text}[/red]")
        except Exception as e:
            console.print(f"[red]Network Error: {e}[/red]")
        return []

    def update_status(self, contact_id, status):
        """Updates the status of a message on the API."""
        headers = {"Authorization": API_TOKEN, "Content-Type": "application/json"}
        payload = {"contact_id": contact_id, "sent_status": status}
        try:
            requests.post(ENDPOINT_UPDATE, headers=headers, json=payload, timeout=10)
        except Exception as e:
            console.print(f"[red]Failed to update status: {e}[/red]")

    def send_message(self, contact):
        """Sends a single message."""
        phone = contact.get('phone')
        msg = contact.get('message', '').replace("<br />", "\n")
        
        if not phone: return False

        encoded_msg = urllib.parse.quote(msg)
        link = f"https://web.whatsapp.com/send?phone={phone}&text={encoded_msg}"
        
        self.driver.get(link)
        
        try:
            # Wait for send button
            # NOTE: WhatsApp Web selectors change often. Using generic attribute matchers.
            send_btn = WebDriverWait(self.driver, 20).until(
                EC.element_to_be_clickable((By.XPATH, "//span[@data-icon='send']"))
            )
            time.sleep(1) # Safety pause
            send_btn.click()
            
            # Wait for the message bubble to appear (confirmation it was put in chat)
            # This is a basic check; strictly speaking 'sent' means one tick, but that's hard to DOM check reliably quickly.
            # We'll assume clicking send works if no error flows occur.
            time.sleep(2) 
            
            self.update_status(contact['id'], 'sent')
            console.print(f"[green]Sent to {phone}[/green]")
            self.last_activity_time = time.time()
            return True
            
        except Exception as e:
            if "alert" in str(e).lower():
                self.update_status(contact['id'], 'invalid')
                console.print(f"[yellow]Invalid number: {phone}[/yellow]")
            else:
                self.update_status(contact['id'], 'failed')
                console.print(f"[red]Failed to send to {phone}: {e}[/red]")
            return False

    def run(self):
        """Main loop."""
        if not self.ensure_session():
            return

        console.print("[cyan]Service Running. Waiting for messages...[/cyan]")
        
        try:
            while True:
                messages = self.fetch_messages()
                
                if messages:
                    # Wake up if dormant
                    if not self.driver:
                        self.start_driver(run_in_background=True)

                    console.print(f"[bold]Found {len(messages)} messages.[/bold]")
                    
                    for msg in messages:
                        self.send_message(msg)
                        time.sleep(5) # Rate limiting
                        
                else:
                    # Check dormant logic
                    if time.time() - self.last_activity_time > DORMANT_THRESHOLD and self.driver:
                        self.stop_driver()
                        console.print("[blue]Going dormant...[/blue]")
                    
                    time.sleep(POLL_INTERVAL)
                    
        except KeyboardInterrupt:
            self.stop_driver()
            console.print("[bold red]Service Stopped[/bold red]")

if __name__ == "__main__":
    gateway = WhatsAppGateway()
    gateway.run()
