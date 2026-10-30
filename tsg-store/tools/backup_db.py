#!/usr/bin/env python3
"""Daily database backup for the store. Run from cron, e.g.  0 2 * * * /usr/bin/python3 /home/USER/public_html/tools/backup_db.py
Reads config/config.php, dumps with mysqldump into ../storage/backups, keeps 14 daily dumps and 8 weekly dumps (Sundays).
On Sundays it also zips /uploads for the weekly full backup. Copy ../storage/backups off the server regularly (Google Drive, another host)."""
import datetime as dt, gzip, pathlib, re, shutil, subprocess, sys, zipfile

ROOT = pathlib.Path(__file__).resolve().parent.parent
OUT = ROOT / 'storage' / 'backups'

def read_config():
    txt = (ROOT / 'config' / 'config.php').read_text()
    def g(key): 
        m = re.search(r"'%s'\s*=>\s*(?:'((?:[^'\\]|\\.)*)'|(\d+))" % key, txt)
        return (m.group(1) if m.group(1) is not None else m.group(2)).replace("\\'", "'").replace('\\\\', '\\')
    return {k: g(k) for k in ('host', 'port', 'name', 'user', 'pass')}

def prune(prefix, keep):
    files = sorted(OUT.glob(prefix + '*'))
    for f in files[:-keep]:
        f.unlink()

def main():
    OUT.mkdir(parents=True, exist_ok=True)
    c = read_config()
    now = dt.datetime.now()
    stamp = now.strftime('%Y%m%d-%H%M')
    weekly = now.weekday() == 6
    target = OUT / f"{'weekly' if weekly else 'daily'}-db-{stamp}.sql.gz"
    cmd = ['mysqldump', f"--host={c['host']}", f"--port={c['port']}", f"--user={c['user']}", f"--password={c['pass']}", '--single-transaction', '--routines', c['name']]
    p = subprocess.run(cmd, capture_output=True)
    if p.returncode != 0:
        sys.exit('mysqldump failed: ' + p.stderr.decode(errors='replace'))
    with gzip.open(target, 'wb') as f:
        f.write(p.stdout)
    print('Database backup:', target)
    if weekly:
        z = OUT / f'weekly-files-{stamp}.zip'
        with zipfile.ZipFile(z, 'w', zipfile.ZIP_DEFLATED) as zf:
            for path in (ROOT / 'uploads').rglob('*'):
                if path.is_file(): zf.write(path, path.relative_to(ROOT))
            for path in (ROOT / 'storage' / 'proofs').rglob('*'):
                if path.is_file(): zf.write(path, path.relative_to(ROOT))
        print('Files backup:', z)
    prune('daily-db-', 14); prune('weekly-db-', 8); prune('weekly-files-', 8)

if __name__ == '__main__':
    main()
