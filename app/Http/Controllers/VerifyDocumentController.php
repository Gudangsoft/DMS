<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\View\View;

class VerifyDocumentController extends Controller
{
    /**
     * Poin 25 — publicly reachable by anyone who scans the QR code. Deliberately
     * unauthenticated: verification must work for outside parties (auditors,
     * accreditation assessors) who don't have a system account.
     */
    public function __invoke(string $uuid): View
    {
        // withTrashed(): an archived document should still resolve so the page can
        // show *why* it's no longer valid, instead of a blank "not found".
        $document = Document::withTrashed()->where('uuid', $uuid)->first();

        $isValid = $document !== null && $document->status->value === 'published';

        return view('verify-document', [
            'document' => $document,
            'isValid' => $isValid,
        ]);
    }
}
