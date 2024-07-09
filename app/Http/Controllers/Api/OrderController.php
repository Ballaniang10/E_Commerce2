<?php
// Test commit du 9 juillet 2024
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderStatusUpdatedMail;

class OrderController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        // Filtrer par utilisateur (les clients ne voient que leurs propres commandes)

        if (!$request->user()->is_admin) {
            $query->where('user_id', $request->user()->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Pagination
        $perPage = $request->get('limit', 15);
        $orders = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        // Check if user can view this order
        if (!$order->user_id === auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $order->load(['user', 'items.product', 'payment']);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:online,cash_on_delivery',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string|max:255',
            'shipping_postal_code' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:255',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $orderItems = [];

            // Récupérer les items du panier si non fournis
            $items = $request->items;
            if (!$items) {
                $cart = \App\Models\Cart::where('user_id', $request->user()->id)->with('items')->first();
                if (!$cart || $cart->items->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Votre panier est vide.'
                    ], 400);
                }
                $items = $cart->items->map(function($item) {
                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity
                    ];
                })->toArray();
            }

             // Valider le stock et calculer le total

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                if (!$product->isActive()) {
                    throw new \Exception("Product {$product->name} is not available");
                }
                if (!$product->hasStock($item['quantity'])) {
                    throw new \Exception("Insufficient stock for product {$product->name}");
                }
                $itemTotal = $product->price * $item['quantity'];
                $totalAmount += $itemTotal;
                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal,
                ];
                // Update stock
                $product->decrement('stock', $item['quantity']);
            }

            // // Créer la commande
            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'online' ? 'pending' : 'pending',
                'total_amount' => $totalAmount,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_postal_code' => $request->shipping_postal_code,
                'shipping_country' => $request->shipping_country,
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Générer la facture
            $invoicePath = $this->invoiceService->generateInvoice($order);
            $order->update(['invoice_path' => $invoicePath]);

            // Vider le panier si utilisé
            if (isset($cart)) {
                $cart->items()->delete();
            }

            // Envoyer l'email de confirmation de commande
            Mail::to($order->user->email)->send(new OrderConfirmationMail($order));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order->load(['items.product', 'payment'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update order status (Admin only).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $request->status]);

        // Send email notification if status changed
        if ($oldStatus !== $request->status) {
            \Mail::to($order->user->email)->send(new OrderStatusUpdatedMail($order));
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order->load(['user', 'items.product'])
        ]);
    }

    /**
     * Download invoice.
     */
    public function downloadInvoice(Order $order)
    {
        // Check if user can download this invoice
        if (!$order->user_id === auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$order->invoice_path) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        $path = storage_path('app/public/' . $order->invoice_path);

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice file not found'
            ], 404);
        }

        return response()->download($path, "invoice-{$order->order_number}.pdf");
    }

    /**
     * GET /api/orders/user
     */
    public function userOrders(Request $request)
    {
        $user = $request->user();
        $orders = Order::where('user_id', $user->id)->get();
        return response()->json(['success' => true, 'data' => $orders]);
    }

    /**
     * PUT /api/orders/{order}/cancel
     */
    public function cancel(Order $order, Request $request)
    {
        $user = $request->user();
        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $order->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Order cancelled']);
    }

    /**
     * PUT /api/orders/{order}/confirm-delivery
     */
    public function confirmDelivery(Order $order, Request $request)
    {
        $user = $request->user();
        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $order->update(['status' => 'delivered']);
        return response()->json(['success' => true, 'message' => 'Delivery confirmed']);
    }

    /**
     * GET /api/orders/track/{trackingNumber}
     */
    public function track($trackingNumber)
    {
        $order = Order::where('tracking_number', $trackingNumber)->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $order]);
    }

    /**
     * GET /api/orders/{order}/history
     */
    public function history(Order $order, Request $request)
    {
        $user = $request->user();
        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Simuler un historique (à adapter selon votre modèle)
        $history = [
            ['status' => 'pending', 'date' => $order->created_at],
            ['status' => 'processing', 'date' => $order->updated_at],
            // Ajouter plus d'étapes selon le statut actuel
        ];

        return response()->json(['success' => true, 'data' => $history]);
    }

    /**
     * POST /api/orders/{order}/return
     */
    public function return(Order $order, Request $request)
    {
        $user = $request->user();
        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $returnData = $request->validate([
            'reason' => 'required|string',
            'items' => 'required|array'
        ]);

        // Logique de retour (à implémenter selon vos besoins)
        return response()->json(['success' => true, 'message' => 'Return request submitted']);
    }

    /**
     * POST /api/orders/{order}/rate
     */
    public function rate(Order $order, Request $request)
    {
        $user = $request->user();
        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $ratingData = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        // Logique de notation (à implémenter selon vos besoins)
        return response()->json(['success' => true, 'message' => 'Rating submitted']);
    }

    /**
     * GET /api/orders/{order}/payment-status
     */
    public function paymentStatus(Order $order, Request $request)
    {
        $user = $request->user();
        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => ['status' => $order->payment_status]]);
    }

    /**
     * POST /api/orders/{order}/payment
     */
    public function payment(Order $order, Request $request)
    {
        $user = $request->user();
        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $paymentData = $request->validate([
            'payment_method' => 'required|string',
            'payment_data' => 'required|array'
        ]);

        // Logique de paiement (à implémenter selon vos besoins)
        return response()->json(['success' => true, 'message' => 'Payment processed']);
    }

    /**
     * POST /api/orders/check-availability
     */
    public function checkAvailability(Request $request)
    {
        $items = $request->input('items', []);
        $availability = [];

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $available = $product && $product->stock >= $item['quantity'];
            $availability[] = [
                'product_id' => $item['product_id'],
                'available' => $available,
                'stock' => $product ? $product->stock : 0
            ];
        }

        return response()->json(['success' => true, 'data' => $availability]);
    }

    /**
     * POST /api/orders/calculate-shipping
     */
    public function calculateShipping(Request $request)
    {
        $shippingData = $request->validate([
            'address' => 'required|string',
            'items' => 'required|array'
        ]);

        // Logique de calcul des frais de livraison (exemple simple)
        $shippingCost = 5.99; // Frais fixes pour l'exemple

        return response()->json(['success' => true, 'data' => ['shipping_cost' => $shippingCost]]);
    }

    /**
     * POST /api/orders/apply-promo
     */
    public function applyPromo(Request $request)
    {
        $code = $request->input('code');
        $total = $request->input('total');

        // Logique de validation du code promo (exemple simple)
        $discount = 0;
        if ($code === 'WELCOME10') {
            $discount = $total * 0.10; // 10% de réduction
        }

        return response()->json([
            'success' => true,
            'data' => [
                'discount' => $discount,
                'final_total' => $total - $discount
            ]
        ]);
    }

    /**
     * Get order statistics (Admin only)
     */
    public function stats(Request $request)
    {
        // Check if user is admin
        if (!$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            // Basic stats
            $stats = [
                'total_orders' => Order::count(),
                'total_revenue' => Order::sum('total_amount'),
                'average_order_value' => Order::avg('total_amount'),

                // Status counts
                'status_counts' => [
                    'pending' => Order::where('status', 'pending')->count(),
                    'processing' => Order::where('status', 'processing')->count(),
                    'shipped' => Order::where('status', 'shipped')->count(),
                    'delivered' => Order::where('status', 'delivered')->count(),
                    'cancelled' => Order::where('status', 'cancelled')->count(),
                ],

                // Payment status counts
                'payment_status_counts' => [
                    'pending' => Order::where('payment_status', 'pending')->count(),
                    'paid' => Order::where('payment_status', 'paid')->count(),
                    'failed' => Order::where('payment_status', 'failed')->count(),
                    'refunded' => Order::where('payment_status', 'refunded')->count(),
                ],

                // Recent orders (last 30 days)
                'recent_orders' => Order::where('created_at', '>=', now()->subDays(30))->count(),
                'recent_revenue' => Order::where('created_at', '>=', now()->subDays(30))->sum('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
