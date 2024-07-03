<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test des APIs via Artisan\n";

// Test 1: Vérifier que les routes existent
echo "\n1. Vérification des routes...\n";
$routes = app('router')->getRoutes();
$apiRoutes = collect($routes)->filter(function($route) {
    return str_starts_with($route->uri(), 'api/');
});

echo "Routes API trouvées: " . $apiRoutes->count() . "\n";

// Test 2: Tester le TestController
echo "\n2. Test du TestController...\n";
try {
    $controller = new \App\Http\Controllers\Api\TestController();
    $response = $controller->test();
    echo "✅ TestController fonctionne: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur TestController: " . $e->getMessage() . "\n";
}

// Test 3: Tester la base de données
echo "\n3. Test de la base de données...\n";
try {
    $userCount = \App\Models\User::count();
    echo "✅ Base de données fonctionne. Utilisateurs: $userCount\n";
} catch (Exception $e) {
    echo "❌ Erreur base de données: " . $e->getMessage() . "\n";
}

// Test 4: Tester les produits
echo "\n4. Test des produits...\n";
try {
    $productCount = \App\Models\Product::count();
    echo "✅ Produits trouvés: $productCount\n";
} catch (Exception $e) {
    echo "❌ Erreur produits: " . $e->getMessage() . "\n";
}

echo "\n🎉 Tests terminés!\n"; 