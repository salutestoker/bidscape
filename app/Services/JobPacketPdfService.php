<?php

namespace App\Services;

use App\Models\JobPacket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class JobPacketPdfService
{
    public function __construct(private readonly CompanySettingsService $settings)
    {
    }

    public function generate(JobPacket $packet): string
    {
        $packet->loadMissing('company', 'job.customer', 'contract');

        $pdf = Pdf::loadView('pdf.job-packet', [
            'packet' => $packet,
            'sections' => $this->settings->enabledSectionKeys($packet->company, 'job_packet'),
        ])->setPaper('letter');
        $path = 'job-packets/'.$packet->packet_number.'.pdf';

        Storage::disk('local')->put($path, $pdf->output());

        $packet->forceFill([
            'pdf_path' => $path,
            'generated_at' => now(),
        ])->save();

        return $path;
    }
}
