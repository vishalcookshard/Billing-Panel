<?php

namespace App\Services\Billing;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService
{
    public function getWalletForUser(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['currency' => 'USD', 'balance' => 0]
        );
    }

    public function credit(User $user, float $amount, string $currency = 'USD'): Wallet
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be greater than zero');
        }

        return DB::transaction(function () use ($user, $amount, $currency) {
            $wallet = $this->getWalletForUser($user);

            // If wallet already has a different currency and non-zero balance, disallow automatic conversion here
            if ($wallet->currency !== $currency && (float) $wallet->balance !== 0.0) {
                throw new \RuntimeException('Currency mismatch on wallet credit');
            }

            // Use BCMath for precise decimal arithmetic and match Wallet cast precision (4)
            $scale = 4;
            $current = (string) $wallet->balance;
            $toAdd = number_format($amount, $scale, '.', '');
            if (function_exists('bcadd')) {
                $new = bcadd($current, $toAdd, $scale);
            } else {
                $new = (string) round((float) $current + (float) $toAdd, $scale);
            }

            $wallet->balance = $new;
            $wallet->currency = $currency;
            $wallet->save();

            return $wallet;
        });
    }

    public function debit(User $user, float $amount, string $currency = 'USD'): Wallet
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Debit amount must be greater than zero');
        }

        return DB::transaction(function () use ($user, $amount, $currency) {
            $wallet = $this->getWalletForUser($user);

            if ($wallet->currency !== $currency) {
                throw new \RuntimeException('Currency mismatch on wallet debit');
            }

            $scale = 4;
            $current = (string) $wallet->balance;
            $toSub = number_format($amount, $scale, '.', '');

            if (function_exists('bcsub')) {
                $new = bcsub($current, $toSub, $scale);
            } else {
                $new = (string) round((float) $current - (float) $toSub, $scale);
            }

            if ((float) $new < 0) {
                throw new \RuntimeException('Insufficient wallet balance');
            }

            $wallet->balance = $new;
            $wallet->save();

            return $wallet;
        });
    }
}
