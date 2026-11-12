<?php

namespace App\Services\Messaging;

use App\Contracts\SmsGatewayInterface;
use App\Models\SmsGateway;

class GatewayManager
{
    private const ADAPTERS = [
        'generic' => GenericHttpGateway::class,
        // future providers register their adapters here
    ];

    public static function adapter(?SmsGateway $gateway = null): SmsGatewayInterface
    {
        $gateway ??= SmsGateway::current();
        $class = self::ADAPTERS[$gateway->provider] ?? GenericHttpGateway::class;

        return app($class);
    }
}
