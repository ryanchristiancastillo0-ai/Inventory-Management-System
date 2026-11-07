# HTTP smoke test for face + passkey routes (run against php -S 127.0.0.1:8099)
$ErrorActionPreference = "Continue"

$ok = $true

# 1. GET auth page (session + csrf)
try {
    $auth = Invoke-WebRequest -Uri 'http://127.0.0.1:8099/auth?auth=login' -SessionVariable session -UseBasicParsing
    $token = [regex]::Match($auth.Content, 'name="csrf_token"\s+value="([^"]+)"').Groups[1].Value
    Write-Host ("1. GET /auth -> {0}, csrf token acquired: {1}..." -f $auth.StatusCode, $token.Substring(0,12))
} catch { Write-Host "1. FAILED: $_"; exit 1 }

# 2. Face challenge
try {
    $opts = Invoke-WebRequest -Uri 'http://127.0.0.1:8099/face/login-options' -WebSession $session -Headers @{ 'X-CSRF-Token' = $token } -UseBasicParsing
    $json = $opts.Content | ConvertFrom-Json
    Write-Host ("2. GET /face/login-options -> {0} (challenge present: {1})" -f $opts.StatusCode, ($null -ne $json.challenge))
    $challenge = $json.challenge
} catch { Write-Host "2. FAILED: $_"; $ok = $false }

# 3. Verify with a fake descriptor (DB has no enrollments -> expect graceful 400 message)
try {
    $body = '{"challenge":"' + $challenge + '","descriptor":[1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]}'
    $verify = Invoke-WebRequest -Uri 'http://127.0.0.1:8099/face/verify' -Method Post -Body $body -ContentType 'application/json' -WebSession $session -Headers @{ 'X-CSRF-Token' = $token } -UseBasicParsing -ErrorAction SilentlyContinue
    $vjson = $verify.Content | ConvertFrom-Json
    Write-Host ("3. POST /face/verify -> {0} (message: {1})" -f $verify.StatusCode, $vjson.message)
} catch {
    $resp = $_.Exception.Response
    $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
    Write-Host ("3. POST /face/verify -> {0} (message: {1})" -f [int]$resp.StatusCode, $reader.ReadToEnd())
}

# 4. CSRF must block requests without header
try {
    $noToken = Invoke-WebRequest -Uri 'http://127.0.0.1:8099/face/login-options' -WebSession $session -UseBasicParsing
    Write-Host ("4. No-CSRF attack not blocked! -> {0}, content-type: {1}" -f $noToken.StatusCode, $noToken.Headers['Content-Type'])
    Write-Host ("   body: {0}" -f $noToken.Content.Substring(0, [Math]::Min(400, $noToken.Content.Length)))
} catch {
    $resp = $_.Exception.Response
    $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
    Write-Host ("4. No-CSRF attack -> {0} (message: {1})" -f [int]$resp.StatusCode, $reader.ReadToEnd())
}

# 5. Passkey (fingerprint) options route
try {
    $pk = Invoke-WebRequest -Uri 'http://127.0.0.1:8099/passkey/login/options' -Method Post -Body '{}' -ContentType 'application/json' -WebSession $session -Headers @{ 'X-CSRF-Token' = $token } -UseBasicParsing
    $pjson = $pk.Content | ConvertFrom-Json
    Write-Host ("5. POST /passkey/login/options -> {0} (publicKey present: {1})" -f $pk.StatusCode, ($null -ne $pjson.options.publicKey))
} catch { Write-Host "5. FAILED: $_"; $ok = $false }

# 6. Admin dashboard protection (unauthenticated must redirect to login)
try {
    $dash = Invoke-WebRequest -Uri 'http://127.0.0.1:8099/admin/dashboard' -WebSession $session -UseBasicParsing -ErrorAction SilentlyContinue
    Write-Host ("6. GET /admin/dashboard (no login) -> {0} (NOT redirected!)" -f $dash.StatusCode)
} catch {
    $resp = $_.Exception.Response
    Write-Host ("6. GET /admin/dashboard (no login) -> {0} redirect: {1}" -f [int]$resp.StatusCode, $resp.Headers['Location'])
}

if ($ok) { Write-Host 'HTTP SMOKE TEST DONE' } else { Write-Host 'SOME CHECKS FAILED'; exit 1 }