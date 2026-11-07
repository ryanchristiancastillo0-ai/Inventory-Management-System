# Downloads face-api.js and its model weights from jsDelivr
# so the app works fully locally. Safe to re-run.
param([string]$Dest = "public\assets\face-api")

$ErrorActionPreference = "Stop"
New-Item -ItemType Directory -Force -Path $Dest, "$Dest\weights" | Out-Null

$base = "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"
$weightsBase = "https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights"

function Get-Remote([string]$Url, [string]$File) {
    if (Test-Path $File) { Write-Host "EXISTS  $File"; return }
    Write-Host "FETCH   $Url"
    Invoke-WebRequest -Uri $Url -OutFile $File -UseBasicParsing
}

# 1. Library
Get-Remote $base "$Dest\face-api.min.js"

# 2. Model manifests
$models = "tiny_face_detector", "face_landmark_68", "face_recognition"
foreach ($m in $models) {
    Get-Remote "$weightsBase/$($m)_model-weights_manifest.json" "$Dest\weights\$($m)_model-weights_manifest.json"
}

# 3. Shards referenced by each manifest
foreach ($m in $models) {
    $manifest = Get-Content "$Dest\weights\$($m)_model-weights_manifest.json" -Raw | ConvertFrom-Json
    foreach ($group in $manifest) {
        foreach ($path in $group.paths) {
            Get-Remote "$weightsBase/$path" "$Dest\weights\$path"
        }
    }
}

Write-Host "DONE"
Get-ChildItem $Dest -Recurse | Select-Object Name, @{n='KB';e={[math]::Round($_.Length/1KB,1)}} | Format-Table -AutoSize