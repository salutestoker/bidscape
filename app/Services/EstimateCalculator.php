<?php

namespace App\Services;

use App\Models\Estimate;

class EstimateCalculator
{
    public function recalculate(Estimate $estimate): Estimate
    {
        $material = 0;
        $labor = 0;
        $equipment = 0;
        $delivery = 0;

        foreach ($estimate->items as $item) {
            $total = MoneyCalculator::multiplyDecimalByCents($item->quantity, $item->unit_price_cents);
            $item->forceFill(['total_cents' => $total])->save();

            $material += $item->material_cost_cents;
            $labor += $item->labor_cost_cents;
            $equipment += $item->equipment_cost_cents;
            $delivery += $item->delivery_cost_cents;
        }

        $direct = $material + $labor + $equipment + $delivery;
        $overhead = MoneyCalculator::percent($direct, $estimate->overhead_basis_points);
        $selling = MoneyCalculator::priceForMargin($direct, $overhead, $estimate->target_margin_basis_points);
        $profit = $selling - $direct - $overhead;

        $estimate->forceFill([
            'material_cost_cents' => $material,
            'labor_cost_cents' => $labor,
            'equipment_cost_cents' => $equipment,
            'delivery_cost_cents' => $delivery,
            'direct_cost_cents' => $direct,
            'overhead_cents' => $overhead,
            'profit_cents' => max(0, $profit),
            'selling_price_cents' => $selling,
            'gross_margin_basis_points' => MoneyCalculator::marginBasisPoints($selling, $direct, $overhead),
        ])->save();

        return $estimate->refresh();
    }
}
