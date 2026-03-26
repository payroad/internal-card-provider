<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalCard\Data;

use Payroad\Port\Provider\Card\CardRefundData;

final class InternalCardRefundData implements CardRefundData
{
    public function __construct(
        private readonly ?string $reason = null,
    ) {}

    public function getReason(): ?string                  { return $this->reason; }
    public function getAcquirerReferenceNumber(): ?string { return null; }

    public function toArray(): array
    {
        return ['reason' => $this->reason];
    }

    public static function fromArray(array $data): static
    {
        return new self(reason: $data['reason'] ?? null);
    }
}
