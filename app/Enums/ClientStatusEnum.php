<?php

namespace App\Enums;

enum ClientStatusEnum: string
{
    case Pending = 'Pending';
    case Approved = 'Approved';
    case Disapproved = 'Disapproved';
}