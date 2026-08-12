<?php

namespace App\Services;

use App\Models\Document;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Poin 25 — every approved & published document gets a unique QR code that
 * links to /verify-document/{uuid}.
 */
class QrCodeService
{
    public function verifyUrl(Document $document): string
    {
        return route('verify-document', ['uuid' => $document->uuid]);
    }

    /**
     * Inline-embeddable SVG data URI — used in Blade/Filament <img src="...">.
     */
    public function dataUri(Document $document): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 6,
            'imageTransparent' => false,
        ]);

        return (new QRCode($options))->render($this->verifyUrl($document));
    }

    /**
     * Raw PNG bytes — used by the "Download QR" action (poin 25).
     */
    public function pngBinary(Document $document): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 12,
            'outputBase64' => false,
            'imageTransparent' => false,
        ]);

        return (new QRCode($options))->render($this->verifyUrl($document));
    }
}
