import requests
import json

url = "https://demo.saafisystems.com/index.php/api/v1/parents/allowed"
headers = {
    "Authorization": "Bearer YOUR_SCHOOL_API_TOKEN",
    "Content-Type": "application/json"
}

try:
    response = requests.get(url, headers=headers)
    print(f"Status Code: {response.status_code}")
    if response.status_code == 200:
        data = response.json()
        print("Response Data:")
        print(json.dumps(data, indent=2))
        print(f"Count: {len(data.get('data', []))}")
    else:
        print(f"Error Body: {response.text}")
except Exception as e:
    print(f"Exception: {e}")
