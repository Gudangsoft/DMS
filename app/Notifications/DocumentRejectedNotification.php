<?php

namespace App\Notifications;

use App\Models\DocumentApproval;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Poin 20: "Dokumen rejected → Notify Owner."
 */
class DocumentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public DocumentApproval $approval) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = $this->approval->document;

        return (new MailMessage)
            ->subject('Dokumen Ditolak — '.$document->title)
            ->line("Dokumen \"{$document->title}\" ditolak oleh reviewer.")
            ->line('Alasan: '.$this->approval->comments)
            ->action('Lihat Dokumen', $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $document = $this->approval->document;

        return FilamentNotification::make()
            ->title('Dokumen ditolak')
            ->body("\"{$document->title}\": {$this->approval->comments}")
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->actions([
                Action::make('view')->label('Lihat')->url($this->url()),
            ])
            ->getDatabaseMessage();
    }

    protected function url(): string
    {
        return route('filament.admin.resources.documents.view', ['record' => $this->approval->document]);
    }
}
