<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use Illuminate\Support\Str;

class ManualPaymentProvider implements PaymentProviderInterface
{
    public function charge(float $amount, string $currency, array $paymentMethodDetails, array $metadata = []): array
    {
        // For manual payments (cash, bank transfer), we simulate a successful transaction
        // Usually, the operator confirms the money is received before calling this.
        
        $transactionId = 'MAN-' . strtoupper(Str::random(10));
        
        return [
            'status' => 'success',
            'transaction_id' => $transactionId,
            'provider' => 'manual',
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => array_merge($metadata, [
                'recorded_by' => auth()->id(),
                'method' => $paymentMethodDetails['type'] ?? 'cash', // cash, bank_transfer, check
                'reference' => $paymentMethodDetails['reference'] ?? null,
            ]),
        ];
    }

    public function refund(string $transactionId, ?float $amount = null, ?string $reason = null): array
    {
        // Manual refunds are just administrative voids
        return [
            'status' => 'success',
            'transaction_id' => $transactionId . '-REFUND',
            'provider' => 'manual',
            'refunded_amount' => $amount, // If null, it means full void
            'reason' => $reason
        ];
    }
}
