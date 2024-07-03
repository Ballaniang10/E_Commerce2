<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Électronique
            [
                'name' => 'Smartphone Samsung Galaxy S23',
                'description' => 'Smartphone haut de gamme avec écran 6.1", 128GB de stockage, appareil photo 50MP',
                'price' => 590000,
                'stock' => 25,
                'category_id' => 1,
                'is_active' => true,
                'slug' => 'smartphone-samsung-galaxy-s23',
                'image' => 'Samsung-Galaxy-S23.jpg'
            ],
            [
                'name' => 'Ordinateur portable Dell Inspiron',
                'description' => 'Ordinateur portable 15.6", Intel i5, 8GB RAM, 512GB SSD',
                'price' => 459000,
                'stock' => 15,
                'category_id' => 1,
                'is_active' => true,
                'slug' => 'ordinateur-portable-dell-inspiron',
                'image' => 'Dell Inspiron 15.jpg'
            ],
            [
                'name' => 'Casque Bluetooth Sony WH-1000XM4',
                'description' => 'Casque sans fil avec réduction de bruit active',
                'price' => 229500,
                'stock' => 30,
                'category_id' => 1,
                'is_active' => true,
                'slug' => 'casque-bluetooth-sony-wh-1000xm4',
                'image' => 'Sony WH-1000XM4.jpg'
            ],

            // Vêtements
            [
                'name' => 'T-shirt en coton bio',
                'description' => 'T-shirt confortable en coton biologique, disponible en plusieurs couleurs',
                'price' => 16500,
                'stock' => 100,
                'category_id' => 2,
                'is_active' => true,
                'slug' => 't-shirt-coton-bio',
                'image' => 'T-shirt coton bio uni.webp'
            ],
            [
                'name' => 'Jean slim fit',
                'description' => 'Jean moderne avec coupe slim, 98% coton, 2% élasthanne',
                'price' => 52500,
                'stock' => 50,
                'category_id' => 2,
                'is_active' => true,
                'slug' => 'jean-slim-fit',
                'image' => 'Jean slim fit bleu.jpg'
            ],

            // Livres
            [
                'name' => 'Le Petit Prince - Antoine de Saint-Exupéry',
                'description' => 'Édition collector du célèbre roman philosophique',
                'price' => 8500,
                'stock' => 75,
                'category_id' => 3,
                'is_active' => true,
                'slug' => 'le-petit-prince-antoine-de-saint-exupery',
                'image' => 'Le Petit Prince couverture.jpg'
            ],
            [
                'name' => 'L\'Étranger - Albert Camus',
                'description' => 'Roman existentialiste de l\'auteur français',
                'price' => 6500,
                'stock' => 60,
                'category_id' => 3,
                'is_active' => true,
                'slug' => 'letranger-albert-camus',
                'image' => 'L\'Étranger Albert Camus.jpg'
            ],

            // Maison & Jardin
            [
                'name' => 'Machine à café automatique',
                'description' => 'Machine à café programmable avec broyeur intégré',
                'price' => 196500,
                'stock' => 20,
                'category_id' => 4,
                'is_active' => true,
                'slug' => 'machine-a-cafe-automatique',
                'image' => 'Machine à café automatique avec broyeur.jpg'
            ],
            [
                'name' => 'Kit de jardinage complet',
                'description' => 'Kit contenant râteau, pelle, sécateur et gants de jardinage',
                'price' => 32750,
                'stock' => 40,
                'category_id' => 4,
                'is_active' => true,
                'slug' => 'kit-de-jardinage-complet',
                'image' => 'Kit jardinage outils.jpg'
            ],

            // Sport & Loisirs
            [
                'name' => 'Vélo de route professionnel',
                'description' => 'Vélo de route en aluminium, 21 vitesses, freins à disque',
                'price' => 590000,
                'stock' => 10,
                'category_id' => 5,
                'is_active' => true,
                'slug' => 'velo-de-route-professionnel',
                'image' => 'Vélo de route carbone.jpeg'
            ],
            [
                'name' => 'Tapis de yoga premium',
                'description' => 'Tapis de yoga antidérapant, épaisseur 5mm, 100% naturel',
                'price' => 26250,
                'stock' => 80,
                'category_id' => 5,
                'is_active' => true,
                'slug' => 'tapis-de-yoga-premium',
                'image' => 'Tapis de yoga premium.jpeg'
            ],

            // Beauté & Santé
            [
                'name' => 'Sérum vitamine C',
                'description' => 'Sérum anti-âge à base de vitamine C pure, 30ml',
                'price' => 19750,
                'stock' => 45,
                'category_id' => 6,
                'is_active' => true,
                'slug' => 'serum-vitamine-c',
                'image' => 'Sérum vitamine C.webp'
            ],
            [
                'name' => 'Diffuseur d\'huiles essentielles',
                'description' => 'Diffuseur ultrasonique avec minuterie, capacité 300ml',
                'price' => 39350,
                'stock' => 35,
                'category_id' => 6,
                'is_active' => true,
                'slug' => 'diffuseur-huiles-essentielles',
                'image' => 'Diffuseur d\'huiles essentielles.jpg'
            ],

            // Jouets & Jeux
            [
                'name' => 'Lego Star Wars - Vaisseau Millenium',
                'description' => 'Set de construction Lego avec 1324 pièces, âge 9+',
                'price' => 98500,
                'stock' => 25,
                'category_id' => 7,
                'is_active' => true,
                'slug' => 'lego-star-wars-vaisseau-millenium',
                'image' => 'Lego Star Wars - Vaisseau Millenium.jpeg'
            ],
            [
                'name' => 'Jeu de société Catan',
                'description' => 'Jeu de stratégie pour 3-4 joueurs, durée 60-90 min',
                'price' => 23000,
                'stock' => 30,
                'category_id' => 7,
                'is_active' => true,
                'slug' => 'jeu-de-societe-catan',
                'image' => 'Jeu de société Catan.webp'
            ],

            // Automobile
            [
                'name' => 'GPS TomTom Go Premium',
                'description' => 'GPS avec cartes Europe, trafic en temps réel, 6"',
                'price' => 131000,
                'stock' => 15,
                'category_id' => 8,
                'is_active' => true,
                'slug' => 'gps-tomtom-go-premium',
                'image' => 'GPS TomTom Go Premium.jpg'
            ],
            [
                'name' => 'Chargeur de voiture USB-C',
                'description' => 'Chargeur rapide 45W avec 2 ports USB-C, compatible toutes voitures',
                'price' => 13000,
                'stock' => 100,
                'category_id' => 8,
                'is_active' => true,
                'slug' => 'chargeur-de-voiture-usb-c',
                'image' => 'Chargeur de voiture USB-C.jpg'
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
} 