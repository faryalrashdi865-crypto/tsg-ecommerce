#!/usr/bin/env python3
"""Shrinks large images already in /uploads (e.g. after bulk copying photos). Needs Pillow:  pip install pillow
Usage: python3 tools/optimize_images.py [--max-width 1200] [--quality 85] [--dry-run]
Only overwrites a file when the result is smaller. Safe to run repeatedly."""
import argparse, pathlib, sys
try:
    from PIL import Image
except ImportError:
    sys.exit('Pillow is not installed. Run: pip install pillow')

ROOT = pathlib.Path(__file__).resolve().parent.parent / 'uploads'

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--max-width', type=int, default=1200)
    ap.add_argument('--quality', type=int, default=85)
    ap.add_argument('--dry-run', action='store_true')
    a = ap.parse_args()
    saved = 0
    for f in ROOT.rglob('*'):
        if f.suffix.lower() not in ('.jpg', '.jpeg', '.png', '.webp'):
            continue
        before = f.stat().st_size
        with Image.open(f) as im:
            im.load()
            if im.width > a.max_width:
                im = im.resize((a.max_width, round(im.height * a.max_width / im.width)), Image.LANCZOS)
            tmp = f.with_suffix(f.suffix + '.tmp')
            fmt = {'.jpg': 'JPEG', '.jpeg': 'JPEG', '.png': 'PNG', '.webp': 'WEBP'}[f.suffix.lower()]
            kw = {'quality': a.quality, 'optimize': True} if fmt != 'PNG' else {'optimize': True}
            if fmt == 'JPEG' and im.mode not in ('RGB', 'L'): im = im.convert('RGB')
            im.save(tmp, fmt, **kw)
        after = tmp.stat().st_size
        if after < before and not a.dry_run:
            tmp.replace(f); saved += before - after
            print(f'{f.relative_to(ROOT.parent)}: {before//1024} KB -> {after//1024} KB')
        else:
            tmp.unlink()
    print(f'Saved {saved//1024} KB' + (' (dry run, nothing changed)' if a.dry_run else ''))

if __name__ == '__main__':
    main()
