<?php

namespace App\Enums;

enum MaterialType: string
{
    case PhysicalMaterial = 'physical_material';
    case Labor = 'labor';
    case Equipment = 'equipment';
    case Delivery = 'delivery';
    case Allowance = 'allowance';
    case Service = 'service';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PhysicalMaterial => 'Physical Material',
            self::Labor => 'Labor',
            self::Equipment => 'Equipment',
            self::Delivery => 'Delivery',
            self::Allowance => 'Allowance',
            self::Service => 'Service',
            self::Other => 'Other',
        };
    }
}
