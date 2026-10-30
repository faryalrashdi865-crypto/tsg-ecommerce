#!/usr/bin/env python3
"""Tech SG Studio internal tool: create a licence key bound to a client's business name and domain.
Usage: python3 tools/generate_licence.py "Kaleido Shop" kaleido.pk
The secret must match LICENCE_SECRET in install/licence.php. Never ship this file to clients."""
import hashlib, hmac, sys

SECRET = 'CHANGE-ME-before-first-sale-tsg-2026'

def licence(business: str, domain: str) -> str:
    msg = f"{business.strip().lower()}|{domain.strip().lower()}".encode()
    h = hmac.new(SECRET.encode(), msg, hashlib.sha256).hexdigest()[:16].upper()
    return 'TSG-' + '-'.join(h[i:i + 4] for i in range(0, 16, 4))

if __name__ == '__main__':
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    print(licence(sys.argv[1], sys.argv[2]))
