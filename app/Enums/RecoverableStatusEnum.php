<?php

namespace App\Enums;

enum RecoverableStatusEnum: string
{
    case Pending = 'PENDING';
    case Recovered = 'RECOVERED';
    case Lost = 'LOST';
}
