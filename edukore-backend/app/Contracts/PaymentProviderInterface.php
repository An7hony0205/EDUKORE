<?php

namespace App\Contracts;

interface PaymentProviderInterface
{
    /**
     * Process a payment charge.
     * 
     * @param float $amount
     * @param string $currency
     * @param array $paymentMethodDetails Details like card token or manual reference
     * @param array $metadata Additional metadata like fee_id, student_id
     * @return array Standardized response containing status, transaction_id, etc.
     */
    public function charge(float $amount, string $currency, array $paymentMethodDetails, array $metadata = []): array;

    /**
     * Process a refund or void a payment.
     * 
     * @param string $transactionId Provider's transaction ID
     * @param float|null $amount Amount to refund (null for full refund/void)
     * @param string|null $reason
     * @return array Standardized response
     */
    public function refund(string $transactionId, ?float $amount = null, ?string $reason = null): array;
}
