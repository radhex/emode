# Build Single Executable Application dla Windows
# Skrypt buduje ksef-pdf.exe - standalone executable bez potrzeby instalacji Node.js

Write-Host "🔨 Budowanie Single Executable Application dla Windows..." -ForegroundColor Cyan
Write-Host ""

# Krok 1: Build bundle
Write-Host "📦 Etap 1: Budowanie bundle..." -ForegroundColor Yellow
npm run build:sea
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Błąd podczas budowania bundle!" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Krok 2: Generowanie SEA blob
Write-Host "🔧 Etap 2: Generowanie SEA preparation blob..." -ForegroundColor Yellow
node --experimental-sea-config sea-config.json
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Błąd podczas generowania blob!" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Krok 3: Kopiowanie node.exe
Write-Host "📋 Etap 3: Kopiowanie node.exe..." -ForegroundColor Yellow
$nodePath = (Get-Command node).Path
Copy-Item $nodePath ksef-pdf.exe -Force
Write-Host "✅ Skopiowano: $nodePath -> ksef-pdf.exe" -ForegroundColor Green
Write-Host ""

# Krok 4: Usunięcie sygnatury (opcjonalnie na Windows)
Write-Host "🔓 Etap 4: Usuwanie sygnatury..." -ForegroundColor Yellow
# Usuń podpis cyfrowy, aby uniknąć ostrzeżenia "signature corrupted"
# Wymaga signtool.exe z Windows SDK
try {
    $signtool = ".\signtool.exe"
    & $signtool remove /s ksef-pdf.exe 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Podpis usunięty" -ForegroundColor Green
    } else {
        Write-Host "ℹ️  Nie można usunąć podpisu (wymaga Windows SDK) - kontynuuję" -ForegroundColor Gray
    }
} catch {
    Write-Host "ℹ️  signtool niedostępny - postject wyświetli ostrzeżenie (nie wpływa na działanie)" -ForegroundColor Gray
}
Write-Host ""

# Krok 4a: Ustawienie metadanych (Assembly Info)
Write-Host "📝 Etap 4a: Ustawianie metadanych pliku (wersja, firma)..." -ForegroundColor Yellow
node set-exe-resources.mjs ksef-pdf.exe
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Błąd podczas ustawiania metadanych!" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Krok 5: Wstrzyknięcie blob używając postject
Write-Host "💉 Etap 5: Wstrzykiwanie blob do executable..." -ForegroundColor Yellow
npx postject ksef-pdf.exe NODE_SEA_BLOB sea-prep.blob `
    --sentinel-fuse NODE_SEA_FUSE_fce680ab2cc467b6e072b8b5df1996b2

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Błąd podczas wstrzykiwania blob!" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Krok 6: Weryfikacja
Write-Host "✅ Etap 6: Weryfikacja..." -ForegroundColor Yellow
if (Test-Path ksef-pdf.exe) {
    $size = (Get-Item ksef-pdf.exe).Length / 1MB
    Write-Host "✅ ksef-pdf.exe utworzony pomyślnie!" -ForegroundColor Green
    Write-Host "📊 Rozmiar: $([math]::Round($size, 2)) MB" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "🧪 Test działania:" -ForegroundColor Cyan
    .\ksef-pdf.exe --help
    
    Write-Host ""
    Write-Host "=" * 70 -ForegroundColor Cyan
    Write-Host "✅ SUKCES! Single Executable Application utworzona!" -ForegroundColor Green
    Write-Host "=" * 70 -ForegroundColor Cyan
    Write-Host ""
    Write-Host "📦 Plik: ksef-pdf.exe" -ForegroundColor White
    Write-Host "📊 Rozmiar: $([math]::Round($size, 2)) MB" -ForegroundColor White
    Write-Host ""
    Write-Host "Użycie:" -ForegroundColor Yellow
    Write-Host "  .\ksef-pdf.exe --help" -ForegroundColor White
    Write-Host "  .\ksef-pdf.exe invoice <plik.xml> <output.pdf>" -ForegroundColor White
    Write-Host "  .\ksef-pdf.exe upo <plik.xml> <output.pdf>" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host "❌ Błąd: ksef-pdf.exe nie został utworzony!" -ForegroundColor Red
    exit 1
}
