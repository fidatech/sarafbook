"""Verify page dependencies are local and vendored CSS/font files are intact."""
from pathlib import Path
import hashlib
import json
import re

root = Path(__file__).resolve().parents[1]
errors = []
for view in (root / 'resources/views').rglob('*.blade.php'):
    for tag in re.findall(r'<(?:script|link|img)\b[^>]*>', view.read_text(), re.I):
        if 'canonical' in tag:
            continue
        if re.search(r'(?:src|href)=["\'](?:https?:)?//', tag):
            errors.append(f'{view.relative_to(root)}: external dependency {tag}')
manifest = json.loads((root / 'public/assets/offline/manifest.json').read_text())
for item in manifest.values():
    path = root / 'public' / item['path']
    if not path.is_file() or hashlib.sha256(path.read_bytes()).hexdigest() != item['sha256']:
        errors.append(f'Missing or changed asset: {path}')
        continue
    if path.suffix == '.css':
        for value in re.findall(r'url\(([^)]+)\)', path.read_text()):
            value = value.strip(' \"\'')
            if not value.startswith(('data:', '#')) and not (path.parent / value).is_file():
                errors.append(f'{path.name}: missing CSS dependency {value}')
assert not errors, '\n'.join(errors)
print(f'PASS: no external page assets; {len(manifest)} vendored assets and CSS dependencies verified.')
