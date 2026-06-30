<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Waived = 'waived';
    case Overdue = 'overdue';
}
