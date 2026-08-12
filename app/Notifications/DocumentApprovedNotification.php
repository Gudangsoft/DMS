<?php

namespace App\Notifications;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Poin 20: "Dokumen approved → Notify Owner."
 */
class DocumentApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Document $document) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dokumen Disetujui — '.$this->document->title)
            ->line("Dokumen \"{$this->document->title}\" telah disetujui.")
            ->action('Lihat Dokumen', $this->url())
            ->line('Dokumen dapat dipublikasikan oleh Admin Dokumen.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Dokumen disetujui')
            ->body("\"{$this->document->title}\" telah disetujui reviewer.")
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->actions([
                Action::make('view')->label('Lihat')->url($this->url()),
            ])
            ->getDatabaseMessage();
    }

    protected function url(): string
    {
        return route('filament.admin.resources.documents.view', ['record' => $this->document]);
    }
}
