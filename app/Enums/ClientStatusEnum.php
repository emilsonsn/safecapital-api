<?php

namespace App\Enums;

enum ClientStatusEnum: string
{
    case Pending = 'Pending';
    case Approved = 'Approved';
    case Disapproved = 'Disapproved';
    case WaitingPayment = 'WaitingPayment';
    case WaitingContract = 'WaitingContract';
    case Active = 'Active';
    case Inactive = 'Inactive';

}