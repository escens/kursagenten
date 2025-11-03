# Kursagenten Release Builder - Interaktiv versjon
# Dette er en wrapper som gjør det enklere å bygge releases

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Kursagenten Release Builder" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Hent nåværende versjon fra kursagenten.php
$MainFile = Join-Path $PSScriptRoot "kursagenten.php"
if (Test-Path $MainFile) {
    $Content = Get-Content $MainFile -Raw -Encoding UTF8
    if ($Content -match "define\('KURSAG_VERSION',\s*'(\d+\.\d+\.\d+)'\);") {
        $CurrentVersion = $matches[1]
        Write-Host "Nåværende versjon: $CurrentVersion" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Skriv inn ny versjonsnummer (f.eks. 1.0.4):" -ForegroundColor White
$Version = Read-Host "Versjon"

if ([string]::IsNullOrWhiteSpace($Version)) {
    Write-Host "Avbrutt." -ForegroundColor Red
    exit 1
}

# Valider format
if ($Version -notmatch '^\d+\.\d+\.\d+$') {
    Write-Host "Ugyldig versjonsnummer format. Bruk format: 1.0.4" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Bygger versjon $Version..." -ForegroundColor Green
Write-Host ""

# Kjør hovedscriptet
& (Join-Path $PSScriptRoot "build-release.ps1") -Version $Version

