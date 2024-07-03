<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'first_name' => 'Kayy',
            'last_name' => 'Dieunede',
            'email' => 'admin@ecommerce.com',
            'password' => Hash::make('password'),
            'phone' => '+33623456789',
            'address' => '15 Avenue des Champs-Élysées',
            'city' => 'Paris',
            'postal_code' => '75008',
            'country' => 'France',
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $admin->assignRole($adminRole);

        // Create realistic French users with more variety
        $users = [
            // Jeunes professionnels
            [
                'first_name' => 'Sophie',
                'last_name' => 'Dubois',
                'email' => 'sophie.dubois@gmail.com',
                'phone' => '+33612345678',
                'address' => '24 Rue de la République',
                'city' => 'Lyon',
                'postal_code' => '69002',
                'country' => 'France',
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Martin',
                'email' => 'thomas.martin@outlook.fr',
                'phone' => '+33634567890',
                'address' => '8 Rue du Vieux Port',
                'city' => 'Marseille',
                'postal_code' => '13002',
                'country' => 'France',
            ],
            [
                'first_name' => 'Emma',
                'last_name' => 'Bernard',
                'email' => 'emma.bernard@yahoo.fr',
                'phone' => '+33645678901',
                'address' => '12 Place du Capitole',
                'city' => 'Toulouse',
                'postal_code' => '31000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Lucas',
                'last_name' => 'Petit',
                'email' => 'lucas.petit@hotmail.fr',
                'phone' => '+33656789012',
                'address' => '5 Rue de la Liberté',
                'city' => 'Bordeaux',
                'postal_code' => '33000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Chloé',
                'last_name' => 'Leroy',
                'email' => 'chloe.leroy@gmail.com',
                'phone' => '+33667890123',
                'address' => '18 Rue des Carmes',
                'city' => 'Orléans',
                'postal_code' => '45000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Antoine',
                'last_name' => 'Moreau',
                'email' => 'antoine.moreau@free.fr',
                'phone' => '+33678901234',
                'address' => '3 Place Kléber',
                'city' => 'Strasbourg',
                'postal_code' => '67000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Julie',
                'last_name' => 'Roux',
                'email' => 'julie.roux@laposte.net',
                'phone' => '+33689012345',
                'address' => '7 Rue de la Paix',
                'city' => 'Nice',
                'postal_code' => '06000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Nicolas',
                'last_name' => 'Fournier',
                'email' => 'nicolas.fournier@sfr.fr',
                'phone' => '+33690123456',
                'address' => '14 Rue du Commerce',
                'city' => 'Tours',
                'postal_code' => '37000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'Laurent',
                'email' => 'marie.laurent@orange.fr',
                'phone' => '+33701234567',
                'address' => '9 Avenue Jean Jaurès',
                'city' => 'Lille',
                'postal_code' => '59000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Pierre',
                'last_name' => 'Michel',
                'email' => 'pierre.michel@gmail.com',
                'phone' => '+33712345678',
                'address' => '22 Boulevard de la Mer',
                'city' => 'Nantes',
                'postal_code' => '44000',
                'country' => 'France',
            ],
            // Ajout d'utilisateurs supplémentaires
            [
                'first_name' => 'Camille',
                'last_name' => 'Garcia',
                'email' => 'camille.garcia@icloud.com',
                'phone' => '+33723456789',
                'address' => '11 Rue de Rivoli',
                'city' => 'Paris',
                'postal_code' => '75004',
                'country' => 'France',
            ],
            [
                'first_name' => 'Alexandre',
                'last_name' => 'Simon',
                'email' => 'alexandre.simon@protonmail.com',
                'phone' => '+33734567890',
                'address' => '6 Place de la Bourse',
                'city' => 'Bordeaux',
                'postal_code' => '33000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Léa',
                'last_name' => 'Lefevre',
                'email' => 'lea.lefevre@yahoo.fr',
                'phone' => '+33745678901',
                'address' => '15 Rue de la Croix-Rousse',
                'city' => 'Lyon',
                'postal_code' => '69004',
                'country' => 'France',
            ],
            [
                'first_name' => 'Hugo',
                'last_name' => 'Girard',
                'email' => 'hugo.girard@hotmail.fr',
                'phone' => '+33756789012',
                'address' => '28 Boulevard de la Madeleine',
                'city' => 'Paris',
                'postal_code' => '75008',
                'country' => 'France',
            ],
            [
                'first_name' => 'Inès',
                'last_name' => 'Bonnet',
                'email' => 'ines.bonnet@gmail.com',
                'phone' => '+33767890123',
                'address' => '4 Rue des Tonneliers',
                'city' => 'Strasbourg',
                'postal_code' => '67000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Raphaël',
                'last_name' => 'Dupont',
                'email' => 'raphael.dupont@free.fr',
                'phone' => '+33778901234',
                'address' => '19 Place du Vieux Marché',
                'city' => 'Rouen',
                'postal_code' => '76000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Zoé',
                'last_name' => 'Lambert',
                'email' => 'zoe.lambert@laposte.net',
                'phone' => '+33789012345',
                'address' => '13 Rue de la République',
                'city' => 'Grenoble',
                'postal_code' => '38000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Louis',
                'last_name' => 'Fontaine',
                'email' => 'louis.fontaine@sfr.fr',
                'phone' => '+33790123456',
                'address' => '7 Place de la Comédie',
                'city' => 'Montpellier',
                'postal_code' => '34000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Alice',
                'last_name' => 'Rousseau',
                'email' => 'alice.rousseau@orange.fr',
                'phone' => '+33801234567',
                'address' => '25 Rue de la Paix',
                'city' => 'Nancy',
                'postal_code' => '54000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Jules',
                'last_name' => 'Blanc',
                'email' => 'jules.blanc@gmail.com',
                'phone' => '+33812345678',
                'address' => '10 Place du Parlement',
                'city' => 'Bordeaux',
                'postal_code' => '33000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Louise',
                'last_name' => 'Henry',
                'email' => 'louise.henry@icloud.com',
                'phone' => '+33823456789',
                'address' => '16 Rue des Halles',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France',
            ],
            [
                'first_name' => 'Adam',
                'last_name' => 'Garnier',
                'email' => 'adam.garnier@protonmail.com',
                'phone' => '+33834567890',
                'address' => '2 Place Bellecour',
                'city' => 'Lyon',
                'postal_code' => '69002',
                'country' => 'France',
            ],
            [
                'first_name' => 'Eva',
                'last_name' => 'Faure',
                'email' => 'eva.faure@yahoo.fr',
                'phone' => '+33845678901',
                'address' => '8 Rue de la Canebière',
                'city' => 'Marseille',
                'postal_code' => '13001',
                'country' => 'France',
            ],
            [
                'first_name' => 'Nathan',
                'last_name' => 'Mercier',
                'email' => 'nathan.mercier@hotmail.fr',
                'phone' => '+33856789012',
                'address' => '14 Place du Capitole',
                'city' => 'Toulouse',
                'postal_code' => '31000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Jade',
                'last_name' => 'Berger',
                'email' => 'jade.berger@gmail.com',
                'phone' => '+33867890123',
                'address' => '21 Rue de la Liberté',
                'city' => 'Nantes',
                'postal_code' => '44000',
                'country' => 'France',
            ],
            [
                'first_name' => 'Ethan',
                'last_name' => 'Moulin',
                'email' => 'ethan.moulin@free.fr',
                'phone' => '+33878901234',
                'address' => '5 Place de l\'Hôtel de Ville',
                'city' => 'Lille',
                'postal_code' => '59000',
                'country' => 'France',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create(array_merge($userData, [
                'password' => Hash::make('password')
            ]));
            
            $clientRole = Role::where('name', 'client')->first();
            $user->assignRole($clientRole);
        }

        // Create a second admin for testing
        $secondAdmin = User::create([
            'first_name' => 'David',
            'last_name' => 'Lambert',
            'email' => 'david.admin@ecommerce.com',
            'password' => Hash::make('password'),
            'phone' => '+33723456789',
            'address' => '30 Rue du Faubourg Saint-Honoré',
            'city' => 'Paris',
            'postal_code' => '75008',
            'country' => 'France',
        ]);

        $secondAdmin->assignRole($adminRole);
    }
} 