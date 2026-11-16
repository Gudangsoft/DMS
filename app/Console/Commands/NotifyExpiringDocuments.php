<?php

namespace App\Console\Commands;

use App\Enums\DocumentValidity;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentExpiringNotification;
use Illuminate\Console\Command;
use Throwable;

/**
 * Daily reminder (see routes/console.php) for documents whose Tanggal
 * Kadaluarsa falls within the next N days. Each document is announced once;
 * changing its expiry date re-arms the reminder (DocumentObserver).
 * Recipients: the document owner plus every Super Admin / Admin Dokumen.
 */
class NotifyExpiringDocuments extends Command
{
    protected $signature = 'dms:notify-expiring {--days=30 : Remind this many days ahead}';

    protected $description = 'Notify owners and admins about documents that are about to expire';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $documents = Document::query()
            ->with('owner')
            ->whereNotNull('expired_date')
            ->whereDate('expired_date', '>=', today())
            ->whereDate('expired_date', '<=', today()->addDays($days))
            ->whereNull('expiry_notified_at')
            ->where('validity', DocumentValidity::Berlaku->value)
            ->get();

        if ($documents->isEmpty()) {
            $this->info('Tidak ada dokumen yang akan kadaluarsa.');

            return self::SUCCESS;
        }

        $admins = User::role([UserRole::SuperAdmin->value, UserRole::AdminDokumen->value])->get();

        foreach ($documents as $document) {
            $recipients = $admins->concat([$document->owner])->filter()->unique('id');

            foreach ($recipients as $user) {
                try {
                    $user->notify(new DocumentExpiringNotification($document));
                } catch (Throwable $e) {
                    // e.g. mail not configured — the in-app notification
                    // (database channel) may still have been stored.
                    report($e);
                }
            }

            $document->forceFill(['expiry_notified_at' => now()])->saveQuietly();
            $this->line("• {$document->title} — {$document->expired_date->format('d/m/Y')}");
        }

        $this->info("{$documents->count()} dokumen diingatkan.");

        return self::SUCCESS;
    }
}
