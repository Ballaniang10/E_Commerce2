<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Électronique',
                'description' => 'Produits électroniques et gadgets',
                'slug' => 'electronique',
            ],
            [
                'name' => 'Vêtements',
                'description' => 'Vêtements et accessoires de mode',
                'slug' => 'vetements',
            ],
            [
                'name' => 'Livres',
                'description' => 'Livres et publications',
                'slug' => 'livres',
            ],
            [
                'name' => 'Maison & Jardin',
                'description' => 'Articles pour la maison et le jardin',
                'slug' => 'maison-jardin',
            ],
            [
                'name' => 'Sport & Loisirs',
                'description' => 'Équipements sportifs et de loisirs',
                'slug' => 'sport-loisirs',
            ],
            [
                'name' => 'Beauté & Santé',
                'description' => 'Produits de beauté et de santé',
                'slug' => 'beaute-sante',
            ],
            [
                'name' => 'Jouets & Jeux',
                'description' => 'Jouets et jeux pour tous âges',
                'slug' => 'jouets-jeux',
            ],
            [
                'name' => 'Automobile',
                'description' => 'Accessoires et pièces automobiles',
                'slug' => 'automobile',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
} 