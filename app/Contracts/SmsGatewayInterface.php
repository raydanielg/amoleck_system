<?php

namespace App\Contracts;

use App\Models\SmsGateway;

interface SmsGatewayInterface
{
    public function send(SmsGateway $gateway, string $phone, string $body): array;

    public function balance(SmsGateway $gateway): ?array;

    public function mapDeliveryReport(array $payload): ?array;
}
