<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalCard\Data;

use Payroad\Domain\Channel\Card\CardAttemptData;
use Payroad\Domain\Channel\Card\ThreeDSData;

final class InternalCardAttemptData implements CardAttemptData
{
    public function __construct(
        private readonly string  $cardholderName  = 'Test Cardholder',
        private readonly string  $bin             = '424242',
        private readonly string  $last4           = '4242',
        private readonly int     $expiryMonth     = 12,
        private readonly int     $expiryYear      = 2030,
        private readonly string  $brand           = 'visa',
        private readonly string  $fundingType     = 'credit',
        private readonly string  $issuingCountry  = 'US',
    ) {}

    public function getBin(): ?string             { return $this->bin; }
    public function getLast4(): ?string           { return $this->last4; }
    public function getExpiryMonth(): ?int        { return $this->expiryMonth; }
    public function getExpiryYear(): ?int         { return $this->expiryYear; }
    public function getCardholderName(): ?string  { return $this->cardholderName; }
    public function getCardBrand(): ?string       { return $this->brand; }
    public function getFundingType(): ?string     { return $this->fundingType; }
    public function getIssuingCountry(): ?string  { return $this->issuingCountry; }
    public function getClientToken(): ?string     { return null; }
    public function requiresUserAction(): bool    { return false; }
    public function getThreeDSData(): ?ThreeDSData { return null; }

    public function toArray(): array
    {
        return [
            'cardholderName' => $this->cardholderName,
            'bin'            => $this->bin,
            'last4'          => $this->last4,
            'expiryMonth'    => $this->expiryMonth,
            'expiryYear'     => $this->expiryYear,
            'brand'          => $this->brand,
            'fundingType'    => $this->fundingType,
            'issuingCountry' => $this->issuingCountry,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            cardholderName: $data['cardholderName'] ?? 'Test Cardholder',
            bin:            $data['bin']            ?? '424242',
            last4:          $data['last4']          ?? '4242',
            expiryMonth:    $data['expiryMonth']    ?? 12,
            expiryYear:     $data['expiryYear']     ?? 2030,
            brand:          $data['brand']          ?? 'visa',
            fundingType:    $data['fundingType']    ?? 'credit',
            issuingCountry: $data['issuingCountry'] ?? 'US',
        );
    }
}
