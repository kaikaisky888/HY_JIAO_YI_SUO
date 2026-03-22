import re

filepath = r'e:\ceshi\coininasia\app\index\view\dealings\recharge.html'
with open(filepath, encoding='utf-8') as f:
    content = f.read()

# Find exact location of omni content block end and pay_address start
idx_omni = content.find('name="omni_address"')
print(f'omni_address field at: {idx_omni}')

# Show the text 150-300 chars after the omni_address field
snippet = content[idx_omni+150:idx_omni+350]
print(repr(snippet))
