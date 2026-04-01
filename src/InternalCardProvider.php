<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalCard;

use Payroad\Domain\Attempt\AttemptStatus;
use Payroad\Domain\Attempt\PaymentAttemptId;
use Payroad\Domain\Money\Money;
use Payroad\Domain\Payment\PaymentId;
use Payroad\Domain\PaymentFlow\Card\CardPaymentAttempt;
use Payroad\Domain\PaymentFlow\Card\CardRefund;
use Payroad\Domain\Refund\RefundId;
use Payroad\Domain\Refund\RefundStatus;
use Payroad\Port\Provider\Card\CapturableCardProviderInterface;
use Payroad\Port\Provider\Card\CaptureResult;
use Payroad\Port\Provider\Card\CardAttemptContext;
use Payroad\Port\Provider\Card\CardRefundContext;
use Payroad\Port\Provider\Card\VoidResult;
use Payroad\Provider\InternalCard\Data\InternalCardAttemptData;
use Payroad\Provider\InternalCard\Data\InternalCardRefundData;

/**
 * Mock card payment provider for demo and testing.
 *
 * Three modes, controlled via factory config:
 *
 *   mode: charge (default)
 *     PENDING → PROCESSING → SUCCEEDED  (immediate sync charge)
 *
 *   mode: authorize
 *     PENDING → AUTHORIZED  (funds held, awaiting explicit capture)
 *     captureAttempt() → PROCESSING → SUCCEEDED
 *     voidAttempt()    → CANCELED
 *
 *   mode: async
 *     PENDING → PROCESSING  (stops here — simulates async provider)
 *     Completion arrives via webhook: PROCESSING → SUCCEEDED
 *     Useful for demonstrating the webhook flow without a real provider.
 *
 * Implements CapturableCardProviderInterface in all modes so the
 * authorize + capture flow can always be tested via the use case layer.
 */
final class InternalCardProvider implements CapturableCardProviderInterface
{
    public function __construct(
        private readonly string $providerName,
        private readonly string $mode = 'charge',
    ) {}

    public function supports(string $providerName): bool
    {
        return $providerName === $this->providerName;
    }

    public function initiateCardAttempt(
        PaymentAttemptId   $id,
        PaymentId          $paymentId,
        string             $providerName,
        Money              $amount,
        CardAttemptContext $context,
    ): CardPaymentAttempt {
        $data    = new InternalCardAttemptData();
        $attempt = CardPaymentAttempt::create($id, $paymentId, $providerName, $amount, $data);
        $attempt->setProviderReference('mock_card_' . $id->value);

        if ($this->mode === 'authorize') {
            $attempt->markAuthorized('mock_authorized');
        } elseif ($this->mode === 'async') {
            $attempt->markProcessing('mock_processing');
        } else {
            $attempt->markProcessing('mock_processing');
            $attempt->markSucceeded('mock_succeeded');
        }

        return $attempt;
    }

    public function captureAttempt(string $providerReference, ?Money $amount = null): CaptureResult
    {
        return new CaptureResult(
            newStatus:      AttemptStatus::SUCCEEDED,
            providerStatus: 'mock_captured',
        );
    }

    public function voidAttempt(string $providerReference): VoidResult
    {
        return new VoidResult(
            newStatus:      AttemptStatus::CANCELED,
            providerStatus: 'mock_voided',
        );
    }

    public function initiateRefund(
        RefundId         $id,
        PaymentId        $paymentId,
        PaymentAttemptId $originalAttemptId,
        string           $providerName,
        Money            $amount,
        string           $originalProviderReference,
        CardRefundContext $context,
    ): CardRefund {
        $data   = new InternalCardRefundData($context->reason);
        $refund = CardRefund::create($id, $paymentId, $originalAttemptId, $providerName, $amount, $data);
        $refund->setProviderReference('mock_refund_' . $id->value);
        $refund->markSucceeded('mock_refund_completed');

        return $refund;
    }

    public function parseIncomingWebhook(array $payload, array $headers): null
    {
        // No webhooks — all transitions happen synchronously.
        return null;
    }
}
