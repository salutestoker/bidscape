<?php

namespace App\Services;

use App\Models\Estimate;

class EstimateSnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(Estimate $estimate): array
    {
        $estimate->loadMissing('customer', 'lead.source', 'items.assembly', 'items.material', 'paymentTerm');

        return [
            'estimate' => $estimate->only([
                'id',
                'estimate_number',
                'project_name',
                'status',
                'material_cost_cents',
                'labor_cost_cents',
                'equipment_cost_cents',
                'delivery_cost_cents',
                'direct_cost_cents',
                'overhead_cents',
                'profit_cents',
                'selling_price_cents',
                'gross_margin_basis_points',
                'scope_summary',
                'exclusions',
                'terms',
            ]),
            'customer' => $estimate->customer?->only(['id', 'name', 'email', 'phone', 'site_address', 'city', 'state', 'postal_code']),
            'lead' => $estimate->lead?->only(['id', 'name', 'email', 'phone', 'lead_source_id', 'project_interest', 'requested_project_specifications', 'site_notes', 'internal_notes', 'gate_code']),
            'payment_term' => $estimate->paymentTerm?->only(['name', 'deposit_basis_points', 'terms_text']),
            'items' => $estimate->items->map(fn ($item) => [
                'name' => $item->name,
                'subtitle' => $item->subtitle,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price_cents' => $item->unit_price_cents,
                'material_cost_cents' => $item->material_cost_cents,
                'labor_cost_cents' => $item->labor_cost_cents,
                'equipment_cost_cents' => $item->equipment_cost_cents,
                'delivery_cost_cents' => $item->delivery_cost_cents,
                'total_cents' => $item->total_cents,
                'item_type' => $item->item_type,
                'assembly' => $item->assembly?->only(['id', 'name', 'category', 'unit']),
                'material' => $item->material?->only(['id', 'name', 'sku', 'category', 'unit']),
                'notes' => $item->notes,
            ])->values()->all(),
        ];
    }
}
