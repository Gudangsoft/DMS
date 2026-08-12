<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\QrCodeService;
use Illuminate\Http\Response;

class QrCodeController extends Controller
{
    public function __invoke(Document $document, QrCodeService $qrCodeService): Response
    {
        $this->authorize('view', $document);

        $png = $qrCodeService->pngBinary($document);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-'.$document->slug.'.png"',
        ]);
    }
}
