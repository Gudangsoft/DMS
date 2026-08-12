<?php

namespace App\Notifications;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Poin 20: "Dokumen disubmit → Notify Reviewer."
 */
class DocumentSubmittedNotification extends Notification
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
            ->subject('Dokumen Baru Menunggu Review — '.$this->document->title)
            ->line("Dokumen \"{$this->document->title}\" telah diajukan dan menunggu review Anda.")
            ->action('Review Dokumen', $this->url())
            ->line('Terima kasih.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Dokumen menunggu review')
            ->body("\"{$this->document->title}\" diajukan oleh {$this->document->owner->name}.")
            ->icon('heroicon-o-document-text')
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
