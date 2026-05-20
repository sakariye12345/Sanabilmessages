import requests
from rich.console import Console

console = Console()

# Configuration (Copied from bridge_service.py)
CI3_BASE_URL = "https://schoolsfls443dr4rsm53m.shihaab.tech"
CI3_TOKEN = "3e8ea952f2a06672"

def get_ci3_headers():
    return {
        "Authorization": CI3_TOKEN,
        "Content-Type": "application/json"
    }

def update_status(ci3_id, status_val):
    url = f"{CI3_BASE_URL}/messages/update_status"
    payload = {
        "contact_id": ci3_id,
        "sent_status": status_val
    }
    try:
        resp = requests.post(url, headers=get_ci3_headers(), json=payload, timeout=10)
        return resp.status_code, resp.text
    except Exception as e:
        return 0, str(e)

if __name__ == "__main__":
    console.print("[bold cyan]🕵️‍♂️ Interactive CI3 Status Tester[/bold cyan]")
    
    # 1. Get ID
    target_id = input("Enter the CI3 ID (e.g. 66) to test: ")
    if not target_id:
        # Fallback fetch
        # (Skipping fetch logic for simplicity, assuming user knows ID 66 works from before)
        target_id = "66"
    
    # 2. Test Loop
    test_values = [
        'viewed', 'opened', 'received', 
        '2', '3', '4', '1',
        'Read', 'SEEN'
    ]
    
    for val in test_values:
        console.print(f"\n👉 Sending status: [bold yellow]'{val}'[/bold yellow]")
        code, body = update_status(target_id, val)
        
        if code == 200:
            console.print("[green]✅ Sent successfully (200 OK)[/green]")
            console.print(f"[bold]Please check your Dashboard for ID {target_id}.[/bold]")
            result = input(f"What does the Status column say? (Enter 'q' to quit): ")
            if result.lower() == 'q':
                break
            console.print(f"📝 Noted: '{val}' -> '{result}'")
        else:
            console.print(f"[red]❌ Request Failed ({code}): {body}[/red]")
            
    console.print("\n[bold green]Test Complete. Thank you![/bold green]")
