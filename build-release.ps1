# Kursagenten Release Builder
# Dette scriptet automatiserer bygging av nye versjoner av Kursagenten plugin
# 
# Bruk: .\build-release.ps1 -Version "1.0.4"

param(
    [Parameter(Mandatory=$true)]
    [string]$Version
)

# Farger for output
function Write-Success { param($msg) Write-Host $msg -ForegroundColor Green }
function Write-Error { param($msg) Write-Host $msg -ForegroundColor Red }
function Write-Info { param($msg) Write-Host $msg -ForegroundColor Cyan }
function Write-Warning { param($msg) Write-Host $msg -ForegroundColor Yellow }

# Valider versjonsnummer format (f.eks. 1.0.4)
if ($Version -notmatch '^\d+\.\d+\.\d+$') {
    Write-Error "Ugyldig versjonsnummer format. Bruk format: 1.0.4"
    exit 1
}

Write-Info "======================================"
Write-Info "Kursagenten Release Builder"
Write-Info "Versjon: $Version"
Write-Info "======================================"
Write-Host ""

# Stier
$PluginDir = $PSScriptRoot
$PluginsDir = Split-Path $PluginDir -Parent
$OutputDir = "C:\Users\ToneBHagen\Websider\Pluginversjoner"
$TempDir = Join-Path $env:TEMP "kursagenten-build-$Version"
$ZipFileName = "kursagenten-$Version.zip"
$ZipFilePath = Join-Path $OutputDir $ZipFileName

# Sjekk at vi er i riktig mappe
if (-not (Test-Path (Join-Path $PluginDir "kursagenten.php"))) {
    Write-Error "Finner ikke kursagenten.php. Kjør scriptet fra plugin-mappen."
    exit 1
}

# Sjekk at output-mappen eksisterer
if (-not (Test-Path $OutputDir)) {
    Write-Info "Oppretter output-mappe: $OutputDir"
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

# Steg 1: Oppdater versjonsnummer i kursagenten.php
Write-Info "Steg 1: Oppdaterer versjonsnummer i kursagenten.php..."
$MainFile = Join-Path $PluginDir "kursagenten.php"
$Content = Get-Content $MainFile -Raw

# Oppdater Plugin Version i header
$Content = $Content -replace "(\* Version:\s+)\d+\.\d+\.\d+", "`${1}$Version"

# Oppdater KURSAG_VERSION konstant
$Content = $Content -replace "(define\('KURSAG_VERSION',\s*')\d+\.\d+\.\d+('\);)", "`${1}$Version`$2"

# Write with UTF8 without BOM (critical to avoid "unexpected output" errors)
$Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($MainFile, $Content, $Utf8NoBomEncoding)

# Force flush to disk
Start-Sleep -Milliseconds 500

# Verifiser at oppdateringen ble skrevet til disk
$VerifyContent = Get-Content $MainFile -Raw
if ($VerifyContent -match "Version:\s+$Version" -and $VerifyContent -match "define\('KURSAG_VERSION',\s*'$Version'\)") {
    Write-Success "Versjonsnummer oppdatert til $Version"
    
    # Verify no BOM
    $Bytes = [System.IO.File]::ReadAllBytes($MainFile)
    if ($Bytes.Length -ge 3 -and $Bytes[0] -eq 0xEF -and $Bytes[1] -eq 0xBB -and $Bytes[2] -eq 0xBF) {
        Write-Error "ADVARSEL: Filen har BOM! Dette vil forårsake 'unexpected output' feil."
        exit 1
    }
    Write-Success "Verifisert: Ingen BOM i filen"
} else {
    Write-Error "FEIL: Versjonsnummer ble ikke oppdatert korrekt!"
    Write-Error "Sjekk at filen ikke er skrivebeskyttet eller apnet i en annen editor."
    exit 1
}

# Steg 2: Påminnelse om CHANGELOG.md
Write-Host ""
Write-Warning "======================================"
Write-Warning "HUSK Å OPPDATERE CHANGELOG.MD!"
Write-Warning "======================================"
Write-Host ""
Write-Host "Legg til folgende i toppen av CHANGELOG.md:" -ForegroundColor Yellow
Write-Host ""
Write-Host "## $Version - $(Get-Date -Format 'yyyy-MM-dd')" -ForegroundColor White
Write-Host "- " -ForegroundColor White
Write-Host ""

# Vent på bekreftelse
Write-Host "Trykk ENTER nar du har oppdatert CHANGELOG.md, eller Ctrl+C for a avbryte..." -ForegroundColor Yellow
$null = Read-Host

# Steg 3: Bygg ZIP-fil med riktig struktur
Write-Info "Steg 3: Bygger ZIP-fil..."

# Rydd opp temp-mappe hvis den eksisterer
if (Test-Path $TempDir) {
    Remove-Item $TempDir -Recurse -Force
}

# Opprett temp-mappe med kursagenten som rotnavn (viktig!)
$TempPluginDir = Join-Path $TempDir "kursagenten"
New-Item -ItemType Directory -Path $TempPluginDir -Force | Out-Null

# Filer og mapper som skal ekskluderes
$ExcludePatterns = @(
    "*.ps1",           # PowerShell-scripts
    "BUILD_README.md", # Build dokumentasjon
    "node_modules",    # Node modules
    ".git",            # Git
    ".gitignore",      # Git ignore
    ".gitattributes",  # Git attributes
    ".vscode",         # VS Code
    ".idea",           # IntelliJ/PHPStorm
    "*.log",           # Log-filer
    "*.tmp",           # Temp-filer
    "*.bak",           # Backup-filer
    ".DS_Store",       # Mac
    "Thumbs.db",       # Windows
    "desktop.ini",     # Windows
    "slettes",         # Slettes-mappe
    "*.zip"            # Gamle ZIP-filer
)

# Kopier alle filer unntatt ekskluderte
Write-Info "Kopierer filer..."

# Funksjon for å sjekke om en sti skal ekskluderes
function Should-Exclude {
    param($Path)
    
    foreach ($Pattern in $ExcludePatterns) {
        if ($Path -like "*$Pattern*") {
            return $true
        }
    }
    return $false
}

# Kopier filer og mapper rekursivt
$FileCount = 0
Get-ChildItem -Path $PluginDir -Recurse -Force | ForEach-Object {
    $Item = $_
    $RelativePath = $Item.FullName.Substring($PluginDir.Length).TrimStart('\', '/')
    
    # Hopp over hvis skal ekskluderes
    if (Should-Exclude $RelativePath) {
        return
    }
    
    $TargetPath = Join-Path $TempPluginDir $RelativePath
    
    if ($Item.PSIsContainer) {
        # Opprett mappe
        if (-not (Test-Path $TargetPath)) {
            New-Item -ItemType Directory -Path $TargetPath -Force | Out-Null
        }
    } else {
        # Kopier fil
        $TargetDir = Split-Path $TargetPath -Parent
        if (-not (Test-Path $TargetDir)) {
            New-Item -ItemType Directory -Path $TargetDir -Force | Out-Null
        }
        Copy-Item -Path $Item.FullName -Destination $TargetPath -Force
        $FileCount++
    }
}

Write-Success "Filer kopiert ($FileCount filer)"

# Verifiser at kopiert kursagenten.php har riktig versjon og ingen BOM
$CopiedMainFile = Join-Path $TempPluginDir "kursagenten.php"
if (Test-Path $CopiedMainFile) {
    $CopiedContent = Get-Content $CopiedMainFile -Raw
    if ($CopiedContent -match "Version:\s+$Version" -and $CopiedContent -match "define\('KURSAG_VERSION',\s*'$Version'\)") {
        Write-Success "Verifisert: Kopiert fil har versjon $Version"
        
        # Verify no BOM in copied file
        $Bytes = [System.IO.File]::ReadAllBytes($CopiedMainFile)
        if ($Bytes.Length -ge 3 -and $Bytes[0] -eq 0xEF -and $Bytes[1] -eq 0xBB -and $Bytes[2] -eq 0xBF) {
            Write-Error "KRITISK FEIL: Kopiert fil har BOM! Dette vil forårsake 'unexpected output' feil."
            Remove-Item $TempDir -Recurse -Force
            exit 1
        }
        Write-Success "Verifisert: Ingen BOM i kopiert fil"
    } else {
        Write-Error "ADVARSEL: Kopiert fil har ikke riktig versjon!"
        Write-Error "Dette kan skyldes at filen var apnet i en editor under bygging."
        $Response = Read-Host "Vil du fortsette likevel? (j/N)"
        if ($Response -ne "j" -and $Response -ne "J") {
            Write-Error "Bygge-prosess avbrutt"
            Remove-Item $TempDir -Recurse -Force
            exit 1
        }
    }
}

# Slett eksisterende ZIP hvis den finnes
if (Test-Path $ZipFilePath) {
    Write-Info "Sletter eksisterende ZIP-fil..."
    Remove-Item $ZipFilePath -Force
}

# Opprett ZIP-fil med riktige path separators (forward slash)
Write-Info "Oppretter ZIP-fil..."

try {
    Add-Type -Assembly "System.IO.Compression.FileSystem"
    Add-Type -Assembly "System.IO.Compression"
    
    # Slett eksisterende arkiv hvis det finnes
    if (Test-Path $ZipFilePath) {
        Remove-Item $ZipFilePath -Force
    }
    
    # Opprett tomt ZIP-arkiv
    $ZipArchive = [System.IO.Compression.ZipFile]::Open($ZipFilePath, [System.IO.Compression.ZipArchiveMode]::Create)
    
    # Konverter til fullt path-format (unngå 8.3 kort filnavn som TONEBH~1)
    $FullBasePath = (Get-Item $TempPluginDir).FullName.TrimEnd('\').TrimEnd('/')
    $BasePathLength = $FullBasePath.Length + 1  # +1 for å hoppe over separator
    
    # Legg til filer manuelt med forward slash i stiene
    Get-ChildItem -Path $TempPluginDir -Recurse -File | ForEach-Object {
        $File = $_
        $FullPath = $File.FullName
        
        # Beregn relativ sti ved å kutte bort base path
        if ($FullPath.Length -gt $BasePathLength) {
            $RelativePath = $FullPath.Substring($BasePathLength)
        } else {
            $RelativePath = $File.Name
        }
        
        # Konverter backslash til forward slash (kritisk for Linux!)
        $RelativePath = $RelativePath.Replace('\', '/')
        
        # Bygg ZIP entry name
        $ZipEntryName = "kursagenten/" + $RelativePath
        
        # Legg til fil i ZIP
        $Entry = $ZipArchive.CreateEntry($ZipEntryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $EntryStream = $Entry.Open()
        $FileStream = [System.IO.File]::OpenRead($File.FullName)
        $FileStream.CopyTo($EntryStream)
        $FileStream.Close()
        $EntryStream.Close()
    }
    
    # Lukk ZIP-arkivet
    $ZipArchive.Dispose()
    
    Write-Success "ZIP-fil opprettet: $ZipFilePath"
} catch {
    Write-Error "Feil ved opprettelse av ZIP-fil: $_"
    Write-Error $_.Exception.Message
    if ($ZipArchive) { $ZipArchive.Dispose() }
    exit 1
}

# Rydd opp temp-mappe
Write-Info "Rydder opp..."
Remove-Item $TempDir -Recurse -Force
Write-Success "Temp-filer slettet"

# Steg 4: Verifiser ZIP-strukturen
Write-Info "Steg 4: Verifiserer ZIP-struktur..."
try {
    $ZipArchive = [System.IO.Compression.ZipFile]::OpenRead($ZipFilePath)
    
    # Sjekk at hovedfilen finnes (kan ha både / og \ som separator)
    $MainFileEntry = $ZipArchive.Entries | Where-Object { 
        $_.FullName -eq "kursagenten/kursagenten.php" -or 
        $_.FullName -eq "kursagenten\kursagenten.php" 
    }
    
    if ($MainFileEntry) {
        Write-Success "ZIP-struktur er korrekt (kursagenten/kursagenten.php funnet)"
        
        # Tell antall filer for info
        $FileCount = ($ZipArchive.Entries | Where-Object { -not $_.FullName.EndsWith('/') -and -not $_.FullName.EndsWith('\') }).Count
        Write-Info "Totalt $FileCount filer pakket"
    } else {
        # Vis de første filene i ZIP for debugging
        $FirstFiles = $ZipArchive.Entries | Select-Object -First 5 -ExpandProperty FullName
        Write-Error "FEIL: kursagenten/kursagenten.php ikke funnet i ZIP!"
        Write-Error "Forste filer i ZIP: $($FirstFiles -join ', ')"
        $ZipArchive.Dispose()
        exit 1
    }
    
    $ZipArchive.Dispose()
} catch {
    Write-Error "Feil ved verifisering av ZIP: $_"
    exit 1
}

# Ferdig!
Write-Host ""
Write-Success "======================================"
Write-Success "RELEASE BYGGET VELLYKKET!"
Write-Success "======================================"
Write-Host ""
Write-Info "Versjon: $Version"
Write-Info "Fil: $ZipFilePath"
Write-Info "Storrelse: $([math]::Round((Get-Item $ZipFilePath).Length / 1MB, 2)) MB"
Write-Host ""
Write-Warning "NESTE STEG:"
Write-Host "1. Last opp $ZipFileName til Kursagenten Server plugin" -ForegroundColor White
Write-Host "2. Test oppdateringen pa en testside forst" -ForegroundColor White
Write-Host "3. Commit endringene til Git hvis alt fungerer" -ForegroundColor White
Write-Host ""

