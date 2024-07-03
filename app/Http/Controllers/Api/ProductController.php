<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $cacheKey = 'products_' . md5(serialize($request->all()));
        
        $products = Cache::remember($cacheKey, 3600, function () use ($request) {
            Log::info('Cache miss for products listing', ['params' => $request->all()]);
            
            $query = Product::with('category')->active();

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Search by name or description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by price range
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filter by stock availability
            if ($request->has('in_stock') && $request->in_stock) {
                $query->inStock();
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['name', 'price', 'created_at', 'stock'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $perPage = min($request->get('limit', 12), 50); // Limit max to 50
            return $query->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $cacheKey = "product_{$product->id}";
        
        $productData = Cache::remember($cacheKey, 3600, function () use ($product) {
            Log::info('Cache miss for product detail', ['product_id' => $product->id]);
            return $product->load('category');
        });

        if (!$productData->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $productData
        ]);
    }

    /**
     * Store a newly created product (Admin only).
     */
    public function store(Request $request)
    {
        $this->authorize('create', Product::class);
        
        Log::info('Admin creating new product', ['admin_id' => $request->user()->id, 'data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $this->handleImageUpload($request->file('image'));
        }

        $product = Product::create($data);
        
        // Clear cache
        $this->clearProductCache();
        
        Log::info('Product created successfully', ['product_id' => $product->id, 'admin_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product->load('category')
        ], 201);
    }

    /**
     * Update the specified product (Admin only).
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);
        
        Log::info('Admin updating product', ['product_id' => $product->id, 'admin_id' => $request->user()->id]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $this->handleImageUpload($request->file('image'));
        }

        $product->update($data);
        
        // Clear cache
        $this->clearProductCache();
        Cache::forget("product_{$product->id}");
        
        Log::info('Product updated successfully', ['product_id' => $product->id, 'admin_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product->fresh()->load('category')
        ]);
    }

    /**
     * Remove the specified product (Admin only).
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        
        Log::info('Admin deleting product', ['product_id' => $product->id, 'admin_id' => auth()->id()]);

        $product->delete();
        
        // Clear cache
        $this->clearProductCache();
        Cache::forget("product_{$product->id}");
        
        Log::info('Product deleted successfully', ['product_id' => $product->id]);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * GET /api/products/featured
     */
    public function featured()
    {
        // Utiliser les produits les plus chers comme "featured" (exemple)
        $products = Product::orderBy('price', 'desc')->limit(6)->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * GET /api/products/category/{categoryId}
     */
    public function byCategory($categoryId)
    {
        $products = Product::where('category_id', $categoryId)->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * GET /api/categories/main
     */
    public function main()
    {
        // Toutes les catégories (puisque pas de parent_id dans la structure actuelle)
        $categories = Category::all();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    /**
     * GET /api/categories/{id}/children
     */
    public function children($id)
    {
        // Retourner un tableau vide car pas de sous-catégories dans la structure actuelle
        return response()->json(['success' => true, 'data' => []]);
    }

    /**
     * GET /api/products/search
     */
    public function search(Request $request)
    {
        $q = $request->input('search');
        $products = Product::where('name', 'ilike', "%$q%")
            ->orWhere('description', 'ilike', "%$q%")
            ->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * POST /api/products/by-ids
     */
    public function byIds(Request $request)
    {
        $products = Product::whereIn('id', $request->ids)
            ->where('is_active', true)
            ->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * Get products by slugs.
     */
    public function bySlugs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slugs' => 'required|array',
            'slugs.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $products = Product::whereIn('slug', $request->slugs)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * GET /api/products/filter
     */
    public function filter(Request $request)
    {
        // Exemple simple : filtrer par prix min/max
        $query = Product::query();
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }
        // Ajouter d'autres filtres selon besoin
        $products = $query->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * GET /api/products/price-ranges
     */
    public function priceRanges()
    {
        $min = Product::min('price');
        $max = Product::max('price');
        return response()->json(['success' => true, 'data' => ['min' => $min, 'max' => $max]]);
    }

    /**
     * Get similar products for a given product.
     */
    public function similar(Product $product)
    {
        $similarProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $similarProducts
        ]);
    }

    /**
     * Clear product cache
     */
    private function clearProductCache()
    {
        Cache::tags(['products'])->flush();
        Cache::forget('products_featured');
        Cache::forget('products_latest');
    }

    /**
     * Handle image upload
     */
    private function handleImageUpload($image)
    {
        if (!$image) {
            return null;
        }

        // Générer un nom unique pour l'image
        $filename = time() . '_' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
        
        // Stocker l'image dans le dossier products
        $path = $image->storeAs('products', $filename, 'public');
        
        return $path;
    }
} 