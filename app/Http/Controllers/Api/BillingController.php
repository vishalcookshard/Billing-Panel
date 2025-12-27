<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\PromoService;
use App\Services\Billing\WalletService;
use App\Services\Billing\CreditService;
use App\Services\Billing\InvoicePdfService;

class BillingController extends Controller
{
    public function applyPromo(Request $request, Invoice $invoice, PromoService $promoService)
    {
        $request->validate(['code' => 'required|string']);

        $result = $promoService->applyPromoToInvoice($request->input('code'), $invoice);

        if (!$result['success']) {
            return response()->json(['error' => $result['message'] ?? 'failed'], 422);
        }

        return response()->json(['success' => true, 'discount' => $result['discount'], 'invoice_id' => $result['invoice_id']]);
    }

    public function wallet(User $user, WalletService $walletService)
    {
        $wallet = $walletService->getWalletForUser($user);
        return response()->json(['user_id' => $user->id, 'balance' => (string)$wallet->balance, 'currency' => $wallet->currency]);
    }

    public function creditWallet(Request $request, User $user, WalletService $walletService)
    {
        $this->authorize('manage-settings');
        $request->validate(['amount' => 'required|numeric|min:0.01']);
        $wallet = $walletService->credit($user, (float)$request->input('amount'), $request->input('currency', 'USD'));
        // Audit the admin credit action
        \App\Models\Audit::log(null, 'admin.credit_wallet', ['user_id' => $user->id, 'amount' => (float)$request->input('amount'), 'currency' => $request->input('currency', 'USD'), 'actor_id' => auth()->id()]);
        return response()->json(['success' => true, 'balance' => (string)$wallet->balance]);
    }

    public function issueCredit(Request $request, CreditService $creditService)
    {
        $this->authorize('manage-settings');
        $request->validate(['amount' => 'required|numeric', 'currency' => 'string']);
        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::find($request->input('invoice_id'));
        }

        $cn = $creditService->issueCredit($invoice, $request->only(['amount', 'currency', 'user_id', 'reason']));
        return response()->json(['success' => true, 'credit_note_id' => $cn->id]);
    }

    public function invoicePdf(Invoice $invoice, InvoicePdfService $pdfService)
    {
        $this->authorize('manage-settings');
        $path = $pdfService->generate($invoice);
        return response()->json(['path' => $path, 'download_url' => url('/storage/' . ltrim($path, '/'))]);
    }
}
