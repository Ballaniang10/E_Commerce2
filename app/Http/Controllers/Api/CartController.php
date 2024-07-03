<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Afficher le panier de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $cart->load('items.product');
        return response()->json([
            'success' => true,
            'data' => $cart
        ]);
    }

    /**
     * Ajouter ou mettre à jour un produit dans le panier
     */
    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $product = Product::findOrFail($request->product_id);
        $item = $cart->items()->where('product_id', $product->id)->first();
        if ($item) {
            $item->quantity += $request->quantity;
            $item->price = $product->price;
            $item->save();
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price,
            ]);
        }
        $cart->load('items.product');
        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'data' => $cart
        ]);
    }

    /**
     * Modifier la quantité d'un produit dans le panier
     */
    public function updateItem(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $item = $cart->items()->where('id', $itemId)->first();
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier'
            ], 404);
        }
        $item->quantity = $request->quantity;
        $item->save();
        $cart->load('items.product');
        return response()->json([
            'success' => true,
            'message' => 'Quantité modifiée',
            'data' => $cart
        ]);
    }

    /**
     * Supprimer un produit du panier
     */
    public function removeItem(Request $request, $itemId)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $item = $cart->items()->where('id', $itemId)->first();
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier'
            ], 404);
        }
        $item->delete();
        $cart->load('items.product');
        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé du panier',
            'data' => $cart
        ]);
    }

    /**
     * Vider le panier
     */
    public function clear(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $cart->items()->delete();
        $cart->load('items.product');
        return response()->json([
            'success' => true,
            'message' => 'Panier vidé',
            'data' => $cart
        ]);
    }
}
