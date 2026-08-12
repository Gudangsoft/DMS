<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentService
{
    public function __construct(
        protected DocumentVersionService $versions,
    ) {}

    /**
     * Create a new document as DRAFT with its first file version (poin 12: CREATE
     * DOCUMENT → DRAFT). $source is either an uploaded file or a link (URL) to a
     * file hosted elsewhere.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile|string $source, User $actor): Document
    {
        return DB::transaction(function () use ($data, $source, $actor) {
            /** @var Document $document */
            $document = Document::create($data);

            $this->versions->store($document, $source, $actor, 'Unggahan awal');

            return $document->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMetadata(Document $document, array $data): Document
    {
        if (! $document->status->isEditable()) {
            throw new RuntimeException('Dokumen dengan status ini tidak dapat diedit langsung.');
        }

        $document->update($data);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateWithNewFile(Document $document, array $data, UploadedFile|string|null $source, User $actor, ?string $changeNotes = null): Document
    {
        if (! $document->status->isEditable()) {
            throw new RuntimeException('Dokumen dengan status ini tidak dapat diedit langsung.');
        }

        return DB::transaction(function () use ($document, $data, $source, $actor, $changeNotes) {
            $document->update($data);

            if ($source) {
                $this->versions->store($document, $source, $actor, $changeNotes);
            }

            return $document->fresh();
        });
    }

    public function publish(Document $document): Document
    {
        if ($document->status !== DocumentStatus::Approved) {
            throw new RuntimeException('Hanya dokumen approved yang dapat dipublikasikan.');
        }

        $document->status = DocumentStatus::Published;
        $document->published_at = now();
        $document->save();

        return $document;
    }

    /**
     * Manual administrative action marking an already-published document as
     * superseded by a newer one (poin 12).
     */
    public function supersede(Document $document): Document
    {
        if ($document->status !== DocumentStatus::Published) {
            throw new RuntimeException('Hanya dokumen published yang dapat ditandai superseded.');
        }

        $document->status = DocumentStatus::Superseded;
        $document->save();

        return $document;
    }

    /**
     * Archive = status change to ARCHIVED + soft delete, so the document leaves
     * normal listings/search (poin 39) but stays recoverable (poin 26).
     */
    public function archive(Document $document): Document
    {
        $document->status = DocumentStatus::Archived;
        $document->archived_at = now();
        $document->save();
        $document->delete();

        return $document;
    }

    public function restore(Document $document): Document
    {
        $document->restore();

        return $document;
    }
}
