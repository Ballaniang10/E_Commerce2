<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        $cacheKey = 'categories_' . md5(serialize($request->all()));
        
        $categories = Cache::remember($cacheKey, 7200, function () use ($request) {
            Log::info('Cache miss for categories listing', ['params' => $request->all()]);
            
            $query = Category::query();

            // Search by name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            // Include products count if requested
            if ($request->has('with_products_count')) {
                $query->withCount('products');
            }

            // Include products if requested
            if ($request->has('with_products')) {
                $query->with(['products' => function ($q) {
                    $q->active()->take(10); // Limit to 10 products per category
                }]);
            }

            // Si pas de pagination demandée, retourner tous les résultats
            if (!$request->has('page') && !$request->has('limit')) {
                return $query->get();
            }

            $perPage = min($request->get('limit', 50), 100);
            return $query->paginate($perPage);
        });

        // Si c'est une collection simple (pas paginée), retourner directement
        if ($categories instanceof \Illuminate\Database\Eloquent\Collection) {
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category, Request $request)
    {
        $cacheKey = "category_{$category->id}_" . md5(serialize($request->all()));
        
        $categoryData = Cache::remember($cacheKey, 3600, function () use ($category, $request) {
            Log::info('Cache miss for category detail', ['category_id' => $category->id]);
            
            $query = Category::where('id', $category->id);
            
            // Include products if requested
            if ($request->has('with_products')) {
                $query->with(['products' => function ($q) use ($request) {
                    $q->active();
                    
                    // Pagination for products within category
                    $page = $request->get('products_page', 1);
                    $perPage = min($request->get('products_limit', 12), 50);
                    $q->skip(($page - 1) * $perPage)->take($perPage);
                }]);
            }
            
            return $query->first();
        });

        return response()->json([
            'success' => true,
            'data' => $categoryData
        ]);
    }

    /**
     * Store a newly created category (Admin only).
     */
    public function store(Request $request)
    {
        $this->authorize('create', Category::class);
        
        Log::info('Admin creating new category', ['admin_id' => $request->user()->id, 'data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create($request->all());
        
        // Clear cache
        $this->clearCategoryCache();
        
        Log::info('Category created successfully', ['category_id' => $category->id, 'admin_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Update the specified category (Admin only).
     */
    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);
        
        Log::info('Admin updating category', ['category_id' => $category->id, 'admin_id' => $request->user()->id]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->all());
        
        // Clear cache
        $this->clearCategoryCache();
        Cache::forget("category_{$category->id}");
        
        Log::info('Category updated successfully', ['category_id' => $category->id, 'admin_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->fresh()
        ]);
    }

    /**
     * Remove the specified category (Admin only).
     */
    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);
        
        Log::info('Admin deleting category', ['category_id' => $category->id, 'admin_id' => auth()->id()]);

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated products'
            ], 422);
        }

        $category->delete();
        
        // Clear cache
        $this->clearCategoryCache();
        Cache::forget("category_{$category->id}");
        
        Log::info('Category deleted successfully', ['category_id' => $category->id]);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
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
     * Clear category-related cache.
     */
    private function clearCategoryCache()
    {
        // Clear all category listing cache
        Cache::tags(['categories'])->flush();
        
        // Alternative: Clear by pattern (if using Redis)
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $keys = Cache::getRedis()->keys('*categories_*');
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }
} 