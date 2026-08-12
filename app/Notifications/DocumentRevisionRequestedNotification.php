<?php

namespace App\Notifications;

use App\Models\DocumentApproval;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Poin 20: "Reviewer meminta revisi → Notify Owner."
 */
class DocumentRevisionRequestedNotification extends Notification
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
            ->subject('Revisi Diminta — '.$document->title)
            ->line("Reviewer meminta revisi untuk dokumen \"{$document->title}\".")
            ->line('Catatan: '.$this->approval->comments)
            ->action('Lihat Dokumen', $this->url())
            ->line('Silakan lakukan revisi dan submit ulang.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $document = $this->approval->document;

        return FilamentNotification::make()
            ->title('Revisi diminta')
            ->body("\"{$document->title}\": {$this->approval->comments}")
            ->icon('heroicon-o-arrow-uturn-left')
            ->iconColor('warning')
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
