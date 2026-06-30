<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Signed = 'signed';
    case Voided = 'voided';
}
