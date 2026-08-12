<?php

namespace App\Models;

use App\Enums\ConfidentialityLevel;
use App\Enums\DocumentStatus;
use App\Observers\DocumentObserver;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Workflow-controlled fields (uuid, slug, status, current_version, download_count,
 * created_by, updated_by, approved_by, approved_at, published_at, archived_at) are
 * intentionally left out of $fillable — they are only ever set by DocumentObserver
 * or the workflow services (DocumentService / DocumentApprovalService), never by
 * mass-assigning raw request input.
 */
#[Fillable([
    'document_number', 'document_code', 'title', 'description',
    'document_category_id', 'document_subcategory_id', 'document_type_id',
    'unit_id', 'owner_id', 'year', 'effective_date', 'expired_date',
    'confidentiality_level', 'is_public',
])]
#[ObservedBy(DocumentObserver::class)]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'effective_date' => 'date',
            'expired_date' => 'date',
            'confidentiality_level' => ConfidentialityLevel::class,
            'status' => DocumentStatus::class,
            'download_count' => 'integer',
            'is_public' => 'boolean',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('document')
            ->logOnly([
                'title', 'status', 'confidentiality_level', 'current_version',
                'document_category_id', 'document_subcategory_id', 'unit_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Poin 19 — audit trail must show the IP address of whoever performed the
     * action; Spatie's activity log has no built-in column for it, so it's
     * carried in `properties` instead.
     */
    public function tapActivity(\Spatie\Activitylog\Contracts\Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->merge([
            'ip_address' => request()->ip(),
        ]);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(DocumentSubcategory::class, 'document_subcategory_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('id');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class)->latest();
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(DocumentDownload::class);
    }

    /**
     * Query-level mirror of DocumentPolicy::view() for the "published only" case —
     * used by catalog/search listings (poin 16, poin 39) so unauthorized rows never
     * even reach the page instead of being filtered one-by-one after the fact.
     * Keep in sync with DocumentPolicy::view() if the access rules ever change.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where('status', DocumentStatus::Published->value)
            ->where(function (Builder $q) use ($user) {
                $q->where('is_public', true)
                    ->orWhere('confidentiality_level', ConfidentialityLevel::PublicLevel->value);

                if ($user === null) {
                    return;
                }

                $q->orWhere('confidentiality_level', ConfidentialityLevel::Internal->value);

                $q->orWhere(function (Builder $unitScope) use ($user) {
                    $unitScope->where('confidentiality_level', ConfidentialityLevel::Unit->value)
                        ->where(function (Builder $unitMatch) use ($user) {
                            $unitMatch->where('unit_id', $user->unit_id)
                                ->when($user->can('documents.view_any'), fn (Builder $b) => $b->orWhereNotNull('unit_id'));
                        });
                });

                if ($user->can('documents.access_restricted') || $user->can('documents.view_any')) {
                    $q->orWhere('confidentiality_level', ConfidentialityLevel::Restricted->value);
                }

                if ($user->can('documents.access_confidential')) {
                    $q->orWhere('confidentiality_level', ConfidentialityLevel::Confidential->value);
                }
            });
    }
}
