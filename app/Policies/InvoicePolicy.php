<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id || $user->hasRole('admin') || $user->is_admin;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        // Only owner or admin can update, but not after paid
        if ($invoice->isPaid()) {
            return false;
        }
        return $user->id === $invoice->user_id || $user->hasRole('admin') || $user->is_admin;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        // Only admin can delete, and not after paid
        if ($invoice->isPaid()) {
            return false;
        }
        return $user->hasRole('admin') || $user->is_admin;
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        // Only owner or admin can pay
        return $user->id === $invoice->user_id || $user->hasRole('admin') || $user->is_admin;
    }
}
