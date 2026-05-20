import requests
from rich.console import Console
from datetime import datetime

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"
console = Console()

TARGET_PHONE = "252634370911"


def get_headers():
    return {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json",
        "Prefer": "return=representation",
    }


def create_test_message():
    console.print(f"[bold cyan]Creating test message for {TARGET_PHONE}...[/bold cyan]")

    msg_data = {
        "school_id": 1,
        "student_id": 1,
        "type": "general",
        "title": "Test Message",
        "body": f"This is a test message sent at {datetime.now().strftime('%H:%M:%S')}.",
        "created_at": datetime.utcnow().isoformat(),
    }

    url_msg = f"{SUPABASE_URL}/rest/v1/messages"
    resp_msg = requests.post(url_msg, headers=get_headers(), json=msg_data, timeout=30)

    if resp_msg.status_code != 201:
        console.print(f"[red]Failed to create message: {resp_msg.text}[/red]")
        return

    new_message = resp_msg.json()[0]
    message_id = new_message["id"]
    console.print(f"[green]Created message ID: {message_id}[/green]")

    recipient_data = {
        "message_id": message_id,
        "phone_number": TARGET_PHONE,
        "status": "pending",
        "ci3_id": f"TEST_MANUAL_{message_id}",
        "is_synced_to_ci3": False,
    }

    url_recipient = f"{SUPABASE_URL}/rest/v1/message_recipients"
    resp_rcpt = requests.post(url_recipient, headers=get_headers(), json=recipient_data, timeout=30)

    if resp_rcpt.status_code == 201:
        console.print(f"[bold green]Success. Test message linked to {TARGET_PHONE}[/bold green]")
    else:
        console.print(f"[red]Failed to link recipient: {resp_rcpt.text}[/red]")


if __name__ == "__main__":
    create_test_message()
