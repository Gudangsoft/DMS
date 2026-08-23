<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DocumentVersionService
{
    /**
     * Allowed document file types (poin 10).
     *
     * @var array<string, string>
     */
    public const ALLOWED_MIME_TYPES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    /**
     * Store a new, immutable version of the given document, from either an
     * uploaded file or a link to a file hosted elsewhere.
     * Poin 11: versi sebelumnya tidak boleh dihapus; versi terbaru menjadi current version.
     */
    public function store(Document $document, UploadedFile|string $source, User $uploader, ?string $changeNotes = null): DocumentVersion
    {
        return $source instanceof UploadedFile
            ? $this->storeUpload($document, $source, $uploader, $changeNotes)
            : $this->storeLink($document, $source, $uploader, $changeNotes);
    }

    /**
     * Poin 43: checksum SHA-256 dihitung dan disimpan untuk validasi integritas.
     */
    protected function storeUpload(Document $document, UploadedFile $file, User $uploader, ?string $changeNotes = null): DocumentVersion
    {
        // The temp upload can be empty or missing if the browser's async upload
        // hadn't actually finished writing to disk when the form was submitted
        // (observed on the single-threaded PHP dev server — see .env). Failing
        // loudly here beats silently recording a 0-byte version that looks fine
        // in the version history but has nothing behind it.
        if (! $file->isValid() || ! is_readable($file->getRealPath()) || $file->getSize() === 0) {
            throw new RuntimeException('File yang diupload kosong atau belum selesai tersimpan. Silakan coba upload ulang.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $disk = config('documents.storage_disk');

        // Random path/filename — the original name is preserved only as metadata,
        // never used on disk, so the file can't be guessed/browsed directly (poin 30).
        $storedPath = Str::uuid()."/".Str::uuid().'.'.$extension;

        $stream = fopen($file->getRealPath(), 'r');
        Storage::disk($disk)->put($storedPath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $checksum = hash_file('sha256', $file->getRealPath());

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version' => $this->nextVersionNumber($document),
            'source_type' => 'upload',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'file_size' => $file->getSize(),
            // getClientMimeType() trusts the browser-supplied header, which Livewire's
            // own temp-upload round trip doesn't always preserve accurately (it has been
            // observed collapsing to a generic application/octet-stream even for PDFs).
            // The extension is validated against ALLOWED_MIME_TYPES by Filament's
            // acceptedFileTypes() before this point, so it's the more trustworthy source.
            'mime_type' => self::ALLOWED_MIME_TYPES[$extension] ?? $this->guessMimeType($file),
            'checksum' => $checksum,
            'change_notes' => $changeNotes,
            'uploaded_by' => $uploader->id,
        ]);

        $document->forceFill(['current_version' => $version->version])->save();

        return $version;
    }

    /**
     * Only reached for an extension outside ALLOWED_MIME_TYPES, which
     * shouldn't happen given upstream validation — but Symfony's
     * File::getMimeType() unconditionally uses PHP's finfo extension, and
     * some minimal hosting PHP builds ship without it enabled. Falling back
     * to the constructor-supplied client mime type avoids a hard crash on
     * those hosts instead of trusting a guess that can't run.
     */
    protected function guessMimeType(UploadedFile $file): ?string
    {
        if (! extension_loaded('fileinfo')) {
            return $file->getClientMimeType() ?: null;
        }

        return $file->getMimeType();
    }

    /**
     * No local bytes means no checksum and no integrity guarantee — the file
     * lives on whatever server the link points to, and can change or disappear
     * without us knowing. This is a deliberate trade-off for documents that are
     * already published elsewhere (e.g. a ministry regulation).
     */
    protected function storeLink(Document $document, string $url, User $uploader, ?string $changeNotes = null): DocumentVersion
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $fileName = $path !== '' ? basename($path) : $document->title;
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version' => $this->nextVersionNumber($document),
            'source_type' => 'link',
            'file_name' => $fileName,
            'file_path' => null,
            'external_url' => $url,
            'file_size' => null,
            'mime_type' => ($extension === 'pdf') ? 'application/pdf' : null,
            'checksum' => null,
            'change_notes' => $changeNotes,
            'uploaded_by' => $uploader->id,
        ]);

        $document->forceFill(['current_version' => $version->version])->save();

        return $version;
    }

    /**
     * 1.0 → 1.1 → 1.2 while the document has never been published; once it has been
     * published at least once, the next upload bumps the major number (poin 11
     * example: 1.0, 1.1, 2.0 — a fresh major version marks "published, then revised").
     */
    protected function nextVersionNumber(Document $document): string
    {
        // The observer defaults current_version to "1.0" the moment the Document
        // row is created — before any file exists. Without this check that
        // placeholder gets treated as a real prior version and the very first
        // upload would be mislabeled "1.1".
        if ($document->versions()->doesntExist()) {
            return '1.0';
        }

        [$major, $minor] = array_pad(explode('.', $document->current_version ?: '1.0'), 2, '0');
        $major = (int) $major;
        $minor = (int) $minor;

        if ($document->published_at !== null) {
            return ($major + 1).'.0';
        }

        return $major.'.'.($minor + 1);
    }

    /**
     * Verify a version's file on disk still matches the checksum recorded at
     * upload time (poin 43). Link-sourced versions have no local bytes or
     * checksum to compare against, so they can never be verified this way.
     */
    public function verifyIntegrity(DocumentVersion $version): bool
    {
        if ($version->isLink()) {
            return false;
        }

        $disk = config('documents.storage_disk');

        if (! Storage::disk($disk)->exists($version->file_path)) {
            return false;
        }

        $absolutePath = Storage::disk($disk)->path($version->file_path);

        return hash_file('sha256', $absolutePath) === $version->checksum;
    }
}
