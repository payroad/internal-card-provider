# payroad/internal-card-provider

Internal (mock) card payment provider for the [Payroad](https://github.com/payroad/payroad-core) platform.

No external API, no card data. Designed for demo, development, and testing all card flow variants
without a real payment processor.

## Features

- Immediate charge: `PENDING → PROCESSING → SUCCEEDED`
- Authorize + capture: `PENDING → AUTHORIZED` → `captureAttempt()` → `SUCCEEDED`
- Void authorization: `voidAttempt()` → `CANCELED`
- Instant refund
- Configurable mode via `payroad.yaml`
- Implements `CapturableCardProviderInterface`

## Configuration

Register one or both modes in `payroad.yaml`:

```yaml
payroad:
    providers:
        internal_card:
            factory: Payroad\Provider\InternalCard\InternalCardProviderFactory
            config:
                provider_name: internal_card
                mode: charge       # PENDING → PROCESSING → SUCCEEDED

        internal_card_auth:
            factory: Payroad\Provider\InternalCard\InternalCardProviderFactory
            config:
                provider_name: internal_card_auth
                mode: authorize    # PENDING → AUTHORIZED (then capture/void)
```

## Payment flows

### Charge mode (`mode: charge`)

```
initiateCardAttempt()
  → PENDING → PROCESSING → SUCCEEDED   (synchronous, no webhooks)
```

### Authorize + capture mode (`mode: authorize`)

```
initiateCardAttempt()
  → PENDING → AUTHORIZED

captureAttempt()
  → PROCESSING → SUCCEEDED

voidAttempt()
  → CANCELED
```

## Implemented interfaces

| Interface | Description |
|-----------|-------------|
| `CardProviderInterface` | Initiation and refund |
| `CapturableCardProviderInterface` | Capture and void of authorized amounts |

---

## Using this as a reference for a real provider

This package covers the core card flow without frontend SDK complexity.
Real card providers (Stripe, Adyen, Checkout.com) add 3DS and tokenization on top.

### File structure to replicate

```
src/
├── YourCardProviderFactory.php     — reads config, constructs provider
├── YourCardProvider.php            — implements CardProviderInterface
└── Data/
    ├── YourCardAttemptData.php     — implements CardAttemptData
    └── YourCardRefundData.php      — implements CardRefundData
```

### What to implement in each file

**`YourCardProviderFactory`** — read API keys and URLs from `$config`, pass to provider constructor.

**`YourCardProvider::initiateCardAttempt()`** — call your API, wrap result in `YourCardAttemptData`,
then build and return a `CardPaymentAttempt`:
```php
$attempt = CardPaymentAttempt::create($id, $paymentId, $providerName, $amount, $data);
$attempt->setProviderReference($apiResponse->chargeId);
// Immediate charge:
$attempt->applyTransition(AttemptStatus::PROCESSING, 'charge_pending');
// OR auth-only:
$attempt->applyTransition(AttemptStatus::AUTHORIZED, 'authorized');
return $attempt;
```

**`YourCardProvider::parseIncomingWebhook()`** — map provider event types to domain statuses:
```php
return new WebhookResult(
    providerReference: $payload['charge_id'],
    newStatus:         AttemptStatus::SUCCEEDED,
    providerStatus:    $payload['status'],
    statusChanged:     true,
);
```

**Optional capabilities** — implement as needed:
- `CapturableCardProviderInterface` — for explicit capture/void support
- `TokenizingCardProviderInterface` — for saved card charges
- `TwoStepCardProviderInterface` — for nonce-based flows (e.g. Braintree Drop-in)
- `OneStepCardProviderInterface` — for client-side SDK flows (e.g. Stripe.js)

Use-case layer checks `instanceof` before calling optional methods — no changes needed in core.

**`YourCardAttemptData`** — implement `CardAttemptData`. Nullable fields (BIN, last4, brand, etc.)
are unknown at creation and populated after the API response or via `updateCardData()`.
Must implement `toArray()` and `static fromArray(array): static`.

**`YourCardRefundData`** — implement `CardRefundData`. Store `reason` and acquirer reference number.

### Checklist

- [ ] `supports()` matches the provider name from `payroad.yaml`
- [ ] `initiateCardAttempt()` sets a `providerReference`
- [ ] `AttemptData::toArray()` / `fromArray()` round-trip without data loss
- [ ] `parseIncomingWebhook()` covers all provider event types — `null` for unknown ones
- [ ] If supporting 3DS: populate `ThreeDSData` and set `requiresUserAction() = true`
- [ ] If supporting capture: implement `CapturableCardProviderInterface`
- [ ] Refund: sync → apply `RefundStatus::SUCCEEDED`; async → leave at `PENDING`
