<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Str;

class UpdateProductSlugs extends Command
{
    protected $signature = 'products:update-slugs';
    protected $description = 'Update slugs for all products';

    public function handle()
    {
        $products = Product::all();
        $count = 0;

        foreach ($products as $product) {
            if (empty($product->slug)) {
                $baseSlug = Str::slug($product->name);
                $slug = $baseSlug;
                $i = 1;

                // Make sure the slug is unique
                while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = $baseSlug . '-' . $i++;
                }

                $product->slug = $slug;
                $product->save();
                $count++;
            }
        }

        $this->info("Updated slugs for {$count} products.");
    }
} 