# PowerShell script to apply all patches to HESK core files
# Usage: .\scripts\apply_patches.ps1

$ErrorActionPreference = "Stop"

Write-Host "Applying HESK customization patches..." -ForegroundColor Cyan

$patchesDir = Join-Path $PSScriptRoot ".." "patches"
$patchesDir = Resolve-Path $patchesDir

if (-not (Test-Path $patchesDir)) {
    Write-Host "Patches directory not found: $patchesDir" -ForegroundColor Red
    exit 1
}

$patches = Get-ChildItem -Path $patchesDir -Filter "*.diff" | Sort-Object Name

if ($patches.Count -eq 0) {
    Write-Host "No patch files found in $patchesDir" -ForegroundColor Yellow
    exit 0
}

Write-Host "Found $($patches.Count) patch file(s)" -ForegroundColor Green

$failed = @()
$applied = @()

foreach ($patch in $patches) {
    Write-Host "`nApplying: $($patch.Name)" -ForegroundColor Yellow
    
    $patchPath = $patch.FullName
    $result = git apply --check $patchPath 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        git apply $patchPath
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  ✓ Applied successfully" -ForegroundColor Green
            $applied += $patch.Name
        } else {
            Write-Host "  ✗ Failed to apply (check failed but apply failed?)" -ForegroundColor Red
            $failed += $patch.Name
        }
    } else {
        Write-Host "  ✗ Patch conflicts or file missing" -ForegroundColor Red
        Write-Host "    You may need to manually apply this patch" -ForegroundColor Yellow
        $failed += $patch.Name
    }
}

Write-Host "`n" + "="*50 -ForegroundColor Cyan
Write-Host "Summary:" -ForegroundColor Cyan
Write-Host "  Applied: $($applied.Count)" -ForegroundColor Green
Write-Host "  Failed:  $($failed.Count)" -ForegroundColor $(if ($failed.Count -eq 0) { "Green" } else { "Red" })

if ($failed.Count -gt 0) {
    Write-Host "`nFailed patches:" -ForegroundColor Red
    foreach ($f in $failed) {
        Write-Host "  - $f" -ForegroundColor Red
    }
    Write-Host "`nPlease review UPGRADE.md for manual patch application instructions." -ForegroundColor Yellow
    exit 1
}

Write-Host "`nAll patches applied successfully!" -ForegroundColor Green
Write-Host "Next: Clear HESK cache and verify functionality." -ForegroundColor Cyan

