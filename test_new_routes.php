<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test des Nouvelles Routes\n";

// Test des nouvelles routes produits
$request = \Illuminate\Http\Request::create('/api/products/featured', 'GET');
$response = app()->handle($request);
echo "Status Featured: " . $response->getStatusCode() . "\n";

$request = \Illuminate\Http\Request::create('/api/products/price-ranges', 'GET');
$response = app()->handle($request);
echo "Status Price Ranges: " . $response->getStatusCode() . "\n";

// Test des nouvelles routes catégories
$request = \Illuminate\Http\Request::create('/api/categories/main', 'GET');
$response = app()->handle($request);
echo "Status Categories Main: " . $response->getStatusCode() . "\n";

// Test d'une route de recherche
$request = \Illuminate\Http\Request::create('/api/products/search?search=samsung', 'GET');
$response = app()->handle($request);
echo "Status Search: " . $response->getStatusCode() . "\n";

echo "✅ Tests des nouvelles routes terminés!\n"; 