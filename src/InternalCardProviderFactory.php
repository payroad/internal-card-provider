<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalCard;

use Payroad\Port\Provider\ProviderFactoryInterface;

final class InternalCardProviderFactory implements ProviderFactoryInterface
{
    public function create(array $config): InternalCardProvider
    {
        return new InternalCardProvider(
            providerName: $config['provider_name'] ?? 'internal_card',
            mode:         $config['mode']          ?? 'charge',
        );
    }
}
