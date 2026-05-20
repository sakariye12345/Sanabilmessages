import urllib.request
import urllib.error
import json

url = 'https://fmmatzjhhyhtkpabyhih.supabase.co/functions/v1/request-otp'
data = b'{"phone": "252634370911"}'
headers = {
    'Authorization': 'Bearer YOUR_SUPABASE_PUBLISHABLE_KEY',
    'Content-Type': 'application/json'
}

req = urllib.request.Request(url, data=data, headers=headers, method='POST')

try:
    response = urllib.request.urlopen(req)
    print("Success:", response.read().decode('utf-8'))
except urllib.error.HTTPError as e:
    print(f"Error {e.code}:", e.read().decode('utf-8'))
except Exception as e:
    print("Unknown Error:", str(e))
