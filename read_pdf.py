import sys
from pypdf import PdfReader

try:
    reader = PdfReader("C:\\Users\\hp\\Downloads\\نموذج الكشف.pdf")
    text = ""
    for page in reader.pages:
        text += page.extract_text() + "\n"
    
    with open("pdf_output.txt", "w", encoding="utf-8") as f:
        f.write(text)
    print("Done")
except Exception as e:
    print(f"Error: {e}")
