<?php

namespace App\Enums;

enum UserValidationEnum: string
{
    case Pending = 'Pending';
    case Accepted = 'Accepted';
    case Return = 'Return';
    case Refused = 'Refused';
}

