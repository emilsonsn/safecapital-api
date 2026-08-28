<?php

namespace App\Enums;

enum BankIntegrationStatusEnum: string
{
    case Connected = 'CONNECTED';
    case Expired = 'EXPIRED';
    case Revoked = 'REVOKED';
    case Error = 'ERROR';
    case Disconnected = 'DISCONNECTED';
}
