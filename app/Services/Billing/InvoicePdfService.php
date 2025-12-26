<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        // Render HTML via a view if present, otherwise a simple fallback
        if (View::exists('invoices.pdf')) {
            $html = View::make('invoices.pdf', ['invoice' => $invoice])->render();
        } else {
            $html = "<h1>Invoice #" . $invoice->id . "</h1><p>Amount: " . $invoice->amount . " " . $invoice->currency . "</p>";
        }

        // Try to use DOMPDF if available
        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->render();
            $output = $dompdf->output();
            $path = 'invoices/invoice-' . $invoice->id . '.pdf';
            Storage::disk(config('filesystems.default'))->put($path, $output);
            return $path;
        }

        // Fallback: save HTML file
        $path = 'invoices/invoice-' . $invoice->id . '.html';
        Storage::disk(config('filesystems.default'))->put($path, $html);
        return $path;
    }
}
