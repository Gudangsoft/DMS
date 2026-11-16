<?php

namespace App\Notifications;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiringNotification extends Notification
{
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
            ->subject('Dokumen Akan Kadaluarsa — '.$this->document->title)
            ->line("Dokumen \"{$this->document->title}\" akan kadaluarsa pada {$this->dateLabel()}.")
            ->line('Mohon siapkan pembaruan/perpanjangan dokumen ini, lalu unggah versi barunya di DMS.')
            ->action('Buka Dokumen', $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Dokumen akan kadaluarsa')
            ->body("\"{$this->document->title}\" kadaluarsa pada {$this->dateLabel()} ({$this->daysLeftLabel()}).")
            ->icon('heroicon-o-clock')
            ->iconColor('warning')
            ->actions([
                Action::make('view')->label('Lihat')->url($this->url()),
            ])
            ->getDatabaseMessage();
    }

    protected function dateLabel(): string
    {
        return $this->document->expired_date->translatedFormat('d F Y');
    }

    protected function daysLeftLabel(): string
    {
        $days = (int) now()->startOfDay()->diffInDays($this->document->expired_date, false);

        return $days <= 0 ? 'hari ini' : "{$days} hari lagi";
    }

    protected function url(): string
    {
        return route('filament.admin.resources.documents.view', ['record' => $this->document]);
    }
}
