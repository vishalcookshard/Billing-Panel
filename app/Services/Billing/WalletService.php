<?php

namespace App\Services\Billing;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getWalletForUser(User $user): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $user->id], ['currency' => 'USD', 'balance' => 0]);
    }

    public function credit(User $user, float $amount, string $currency = 'USD')
    {
        return DB::transaction(function () use ($user, $amount, $currency) {
            $wallet = $this->getWalletForUser($user);
            $wallet->balance += $amount;
            $wallet->currency = $currency;
            $wallet->save();
            return $wallet;
        });
    }

    public function debit(User $user, float $amount, string $currency = 'USD')
    {
        return DB::transaction(function () use ($user, $amount, $currency) {
            $wallet = $this->getWalletForUser($user);
            if ($wallet->currency !== $currency) {
                // For now, do not handle conversion here
                throw new \RuntimeException('Currency mismatch on wallet debit');
            }
            if ($wallet->balance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance');
            }
            $wallet->balance -= $amount;
            $wallet->save();
            return $wallet;
        });
    }
}
