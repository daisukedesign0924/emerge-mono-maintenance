#!/usr/bin/env python3
"""Build an installable WordPress ZIP from a repository, without a Mac."""
import argparse
import hashlib
import re
import stat
import zipfile
from pathlib import Path

p = argparse.ArgumentParser()
p.add_argument('repository', type=Path)
p.add_argument('--output', type=Path, default=Path('dist'))
args = p.parse_args()
root = args.repository.resolve()
mains = []
for file in root.glob('*.php'):
    header = file.read_text(encoding='utf-8')[:8192]
    if re.search(r'^\s*\*?\s*Plugin Name:', header, re.M):
        mains.append((file, header))
if len(mains) != 1:
    p.error('Repository must contain exactly one main plugin PHP file')
main, header = mains[0]
match = re.search(r'^\s*\*?\s*Version:\s*([^\s]+)', header, re.M)
if not match or not re.fullmatch(r'\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?', match[1]):
    p.error('Missing or invalid plugin version')
slug = 'emerge-mono-journal' if main.name == 'emerge-mono.php' else main.stem
version = match[1]
skip = {'.git', '.github', '.codex', '.idea', 'node_modules', 'vendor',
        'dist', 'build', 'tests', 'scripts', '__pycache__'}
devfiles = {'AGENTS.md', 'CLOUD-DEVELOPMENT.md', 'package-plugin.py',
            '.DS_Store', 'wp-config.php', 'config.php', 'source-comparison.json'}
files = []
for file in sorted(root.rglob('*')):
    rel = file.relative_to(root)
    if any(part in skip or part.startswith('.') for part in rel.parts):
        continue
    if file.is_symlink():
        p.error('Symlinks are not allowed: ' + str(rel))
    if not file.is_file() or file.name in devfiles:
        continue
    if file.suffix.lower() in {'.zip', '.pem', '.key', '.log', '.sqlite', '.db'}:
        p.error('Unexpected archive, credential or local data: ' + str(rel))
    files.append((file, rel))
args.output.mkdir(parents=True, exist_ok=True)
archive = args.output / f'{slug}-{version}.zip'
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as z:
    for file, rel in files:
        info = zipfile.ZipInfo(f'{slug}/{rel.as_posix()}', (2026, 1, 1, 0, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        info.external_attr = (stat.S_IFREG | 0o644) << 16
        z.writestr(info, file.read_bytes())
with zipfile.ZipFile(archive) as z:
    if z.testzip() is not None or f'{slug}/{main.name}' not in z.namelist():
        raise RuntimeError('ZIP validation failed')
checksum = hashlib.sha256(archive.read_bytes()).hexdigest()
archive.with_suffix('.zip.sha256').write_text(f'{checksum}  {archive.name}\n')
print(f'{archive}: {len(files)} files, SHA-256 {checksum}')
