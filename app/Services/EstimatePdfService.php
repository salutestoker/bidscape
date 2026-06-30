<?php

namespace App\Services;

use App\Models\Estimate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class EstimatePdfService
{
    public function __construct(private readonly CompanySettingsService $settings)
    {
    }

    public function generate(Estimate $estimate): string
    {
        $estimate->loadMissing('company', 'customer', 'lead.source', 'items.assembly', 'items.material', 'paymentTerm');

        $path = 'estimates/'.$estimate->estimate_number.'.pdf';
        $pdf = Pdf::loadView('pdf.estimate', [
            'estimate' => $estimate,
            'sections' => $this->settings->enabledSectionKeys($estimate->company, 'estimate'),
        ])->setPaper('letter');

        Storage::disk('local')->put($path, $pdf->output());

        $estimate->forceFill(['pdf_path' => $path])->save();

        return $path;
    }
}
