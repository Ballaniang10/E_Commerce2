#!/bin/bash

echo "🚀 Installation du backend E-Commerce Laravel..."

# Vérifier si composer est installé
if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

# Vérifier si PHP est installé
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

echo "📦 Installation des dépendances Composer..."
composer install

echo "🔧 Configuration de l'environnement..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ Fichier .env créé"
else
    echo "⚠️  Fichier .env existe déjà"
fi

echo "🔑 Génération de la clé d'application..."
php artisan key:generate

echo "🗄️  Configuration de la base de données..."
echo "Assurez-vous d'avoir configuré votre base de données dans le fichier .env"
read -p "Voulez-vous exécuter les migrations maintenant? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate
    echo "✅ Migrations exécutées"
fi

echo "🌱 Exécution des seeders..."
read -p "Voulez-vous exécuter les seeders pour les données de test? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed
    echo "✅ Données de test créées"
fi

echo "📂 Création des liens symboliques..."
php artisan storage:link

echo "🔄 Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "📋 Configuration des permissions Spatie..."
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

echo "📄 Configuration d'Activity Log..."
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"

echo "📊 Publication des assets DomPDF..."
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"

echo ""
echo "🎉 Installation terminée!"
echo ""
echo "📝 Prochaines étapes:"
echo "1. Configurez votre base de données dans .env"
echo "2. Configurez Stripe dans .env (STRIPE_KEY, STRIPE_SECRET)"
echo "3. Configurez votre serveur de mail dans .env"
echo "4. Démarrez le serveur: php artisan serve"
echo ""
echo "🔗 URLs importantes:"
echo "- API: http://localhost:8000/api"
echo "- Admin: admin@ecommerce.com (password: password)"
echo "- Client test: client@test.com (password: password)"
echo ""
echo "📚 Documentation API disponible dans routes/api.php" 