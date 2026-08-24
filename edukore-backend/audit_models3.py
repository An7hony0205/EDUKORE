# -*- coding: utf-8 -*-
import glob
import re
import os

for filepath in glob.glob('app/Models/*.php'):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        matches = re.finditer(r'public\s+function\s+(\w+)\s*\([^)]*\)\s*(?::\s*[\w\\]+\s*)?\{(?:[^}]*?)return\s+\$this->(belongsTo|hasMany|hasOne|belongsToMany|hasManyThrough)\s*\(\s*([^;]+)\s*\);', content, re.DOTALL)
        found = False
        for m in matches:
            if not found:
                print(f"--- {os.path.basename(filepath)} ---")
                found = True
            print(f"  {m.group(1)} -> {m.group(2)}({m.group(3).strip()})")
