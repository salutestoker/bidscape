<?php

namespace App\Enums;

enum JobStatus: string
{
    case Sold = 'sold';
    case ContractPending = 'contract_pending';
    case PacketReady = 'packet_ready';
    case HandedOff = 'handed_off';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Sold => 'Sold',
            self::ContractPending => 'Contract Pending',
            self::PacketReady => 'Packet Ready',
            self::HandedOff => 'Handed Off',
            self::Archived => 'Archived',
        };
    }
}
