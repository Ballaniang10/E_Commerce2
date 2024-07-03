<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Génère une facture PDF pour une commande et retourne le chemin du fichier.
     */
    public function generateInvoice(Order $order)
    {
        $order->load(['user', 'items.product']);
        $data = [
            'order' => $order,
            'user' => $order->user,
            'items' => $order->items,
            'date' => $order->created_at->format('d/m/Y'),
        ];
        $pdf = Pdf::loadView('invoices.invoice', $data);
        $fileName = 'invoices/invoice-' . $order->id . '-' . time() . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());
        return $fileName;
    }
} 