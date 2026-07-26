import requests
from rich.console import Console
import json
import os

API_TOKEN = os.environ.get("CI3_API_TOKEN", "").strip()
if not API_TOKEN:
    raise RuntimeError("CI3_API_TOKEN environment variable is required")
ENDPOINT_FETCH = "https://schoolsfls443dr4rsm53m.shihaab.tech/messages/contacts"

console = Console()

def inspect_api():
    console.print(f"[bold cyan]🔍 Inspecting CI3 API...[/bold cyan]")
    
    headers = {"Authorization": API_TOKEN}
    try:
        resp = requests.get(ENDPOINT_FETCH, headers=headers, timeout=10)
        
        console.print(f"Status Code: {resp.status_code}")
        
        if resp.status_code == 200:
            data = resp.json()
            console.print(f"[green]✅ Connection Success![/green]")
            console.print(f"Items found: {len(data)}")
            
            if data:
                console.print("[bold]First Item Structure:[/bold]")
                console.print(json.dumps(data[0], indent=2))
            else:
                console.print("[yellow]No pending messages found in CI3 (Queue empty).[/yellow]")
        else:
            console.print(f"[red]❌ Error: {resp.text}[/red]")

    except Exception as e:
        console.print(f"[red]Network Failure: {e}[/red]")

if __name__ == "__main__":
    inspect_api()
