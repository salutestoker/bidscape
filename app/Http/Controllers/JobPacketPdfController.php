<?php

namespace App\Http\Controllers;

use App\Models\JobPacket;
use App\Services\JobPacketPdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class JobPacketPdfController extends Controller
{
    public function __invoke(JobPacket $packet, JobPacketPdfService $pdf): Response
    {
        $path = $packet->pdf_path ?: $pdf->generate($packet);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$packet->packet_number.'.pdf"',
        ]);
    }
}
