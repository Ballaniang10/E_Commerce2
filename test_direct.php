<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test Direct des APIs\n";

// Simuler une requête HTTP
$request = \Illuminate\Http\Request::create('/api/test', 'GET');
$response = app()->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Contenu: " . $response->getContent() . "\n";

// Test des produits
$request = \Illuminate\Http\Request::create('/api/products', 'GET');
$response = app()->handle($request);

echo "\nStatus Produits: " . $response->getStatusCode() . "\n";
echo "Contenu Produits: " . $response->getContent() . "\n";

// Test des catégories
$request = \Illuminate\Http\Request::create('/api/categories', 'GET');
$response = app()->handle($request);

echo "\nStatus Catégories: " . $response->getStatusCode() . "\n";
echo "Contenu Catégories: " . $response->getContent() . "\n";

echo "\n✅ Tests terminés!\n"; 