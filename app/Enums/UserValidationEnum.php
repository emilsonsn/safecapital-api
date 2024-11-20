<?php

namespace App\Enums;

enum UserValidationEnum: string
{
    case Pending = 'Pending';
    case Accepted = 'Accepted';
    case Refused = 'Refused';
}

