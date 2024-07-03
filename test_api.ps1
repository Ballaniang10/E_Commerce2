# Script de test des APIs E-Commerce
Write-Host "🧪 Test des APIs E-Commerce" -ForegroundColor Green

$baseUrl = "http://127.0.0.1:8000/api"

# Test 1: Endpoint de test
Write-Host "`n1. Test de l'endpoint de test..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/test" -Method GET -Headers @{"Accept"="application/json"}
    Write-Host "✅ Succès: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Réponse: $($response.Content)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: Liste des produits (public)
Write-Host "`n2. Test de la liste des produits..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/products" -Method GET -Headers @{"Accept"="application/json"}
    Write-Host "✅ Succès: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Réponse: $($response.Content)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Liste des catégories (public)
Write-Host "`n3. Test de la liste des catégories..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/categories" -Method GET -Headers @{"Accept"="application/json"}
    Write-Host "✅ Succès: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Réponse: $($response.Content)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 4: Inscription d'un utilisateur
Write-Host "`n4. Test d'inscription d'utilisateur..." -ForegroundColor Yellow
$registerData = @{
    first_name = "Test"
    last_name = "User"
    email = "test@example.com"
    password = "password123"
    password_confirmation = "password123"
    phone = "+33123456789"
    address = "123 Test Street"
    city = "Paris"
    postal_code = "75001"
    country = "France"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/auth/register" -Method POST -Body $registerData -Headers @{"Accept"="application/json"; "Content-Type"="application/json"}
    Write-Host "✅ Succès: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Réponse: $($response.Content)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 5: Connexion utilisateur
Write-Host "`n5. Test de connexion utilisateur..." -ForegroundColor Yellow
$loginData = @{
    email = "client@test.com"
    password = "password"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/auth/login" -Method POST -Body $loginData -Headers @{"Accept"="application/json"; "Content-Type"="application/json"}
    Write-Host "✅ Succès: $($response.StatusCode)" -ForegroundColor Green
    $loginResponse = $response.Content | ConvertFrom-Json
    $token = $loginResponse.data.token
    Write-Host "Token obtenu: $($token.Substring(0, 20))..." -ForegroundColor Cyan
    
    # Test 6: Récupération du profil utilisateur
    Write-Host "`n6. Test de récupération du profil utilisateur..." -ForegroundColor Yellow
    $userResponse = Invoke-WebRequest -Uri "$baseUrl/auth/user" -Method GET -Headers @{"Accept"="application/json"; "Authorization"="Bearer $token"}
    Write-Host "✅ Succès: $($userResponse.StatusCode)" -ForegroundColor Green
    Write-Host "Profil: $($userResponse.Content)" -ForegroundColor Cyan
    
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n🎉 Tests terminés!" -ForegroundColor Green 