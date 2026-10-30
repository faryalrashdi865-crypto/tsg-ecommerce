<?php
/* Licence keys are created only by Tech SG Studio (tools/generate_licence.py) and are bound to the
   client's business name and domain. NOTE: this HMAC check keeps honest installations honest. The secret
   ships inside the script, so for stronger protection Phase 3 should move to signed keys (public-key
   verification) or an online check. Change LICENCE_SECRET before the first client sale. */
const LICENCE_SECRET = 'CHANGE-ME-before-first-sale-tsg-2026';

function licence_expected(string $business, string $domain): string {
    $h = strtoupper(substr(hash_hmac('sha256', strtolower(trim($business)) . '|' . strtolower(trim($domain)), LICENCE_SECRET), 0, 16));
    return 'TSG-' . implode('-', str_split($h, 4));
}
function licence_valid(string $key, string $business, string $domain): bool {
    return hash_equals(licence_expected($business, $domain), strtoupper(trim($key)));
}
