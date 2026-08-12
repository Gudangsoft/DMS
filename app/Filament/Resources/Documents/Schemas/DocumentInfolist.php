<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Services\QrCodeService;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Poin 22 — Document Detail: seluruh field yang diminta ditampilkan di sini;
 * riwayat versi/approval/komentar/download berada di relation manager tabs
 * pada ViewDocument page, dan audit trail di ActivitylogRelationManager.
 */
class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dokumen')
                    ->columns(3)
                    ->components([
                        TextEntry::make('title')->label('Nama Dokumen')->columnSpan(3),
                        TextEntry::make('document_number')->label('Nomor Dokumen')->placeholder('-'),
                        TextEntry::make('document_code')->label('Kode Dokumen')->placeholder('-'),
                        TextEntry::make('current_version')->label('Versi'),
                        TextEntry::make('category.name')->label('Kategori'),
                        TextEntry::make('subcategory.name')->label('Subkategori')->placeholder('-'),
                        TextEntry::make('type.name')->label('Jenis Dokumen'),
                        TextEntry::make('unit.name')->label('Unit Pemilik'),
                        TextEntry::make('owner.name')->label('Pemilik Dokumen'),
                        TextEntry::make('year')->label('Tahun'),
                        TextEntry::make('effective_date')->label('Tanggal Berlaku')->date('d F Y')->placeholder('-'),
                        TextEntry::make('expired_date')->label('Tanggal Kadaluarsa')->date('d F Y')->placeholder('-'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('confidentiality_level')->label('Tingkat Akses')->badge(),
                        TextEntry::make('description')->label('Deskripsi')->columnSpan(3)->placeholder('-'),
                    ]),

                Section::make('Verifikasi QR')
                    ->columns(2)
                    ->visible(fn ($record) => $record->status->value === 'published')
                    ->components([
                        ImageEntry::make('qr')
                            ->label('QR Code')
                            ->state(fn ($record) => app(QrCodeService::class)->dataUri($record))
                            ->height(150),
                        TextEntry::make('verify_url')
                            ->label('URL Verifikasi')
                            ->state(fn ($record) => app(QrCodeService::class)->verifyUrl($record))
                            ->copyable(),
                    ]),
            ]);
    }
}
