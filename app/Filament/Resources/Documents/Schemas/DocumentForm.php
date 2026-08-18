<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\ConfidentialityLevel;
use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use App\Models\DocumentType;
use App\Models\Unit;
use App\Models\User;
use App\Services\DocumentNumberService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dokumen')
                    ->columns(2)
                    ->components([
                        TextInput::make('title')
                            ->label('Judul Dokumen')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),

                        Select::make('document_category_id')
                            ->label('Kategori')
                            ->options(fn () => DocumentCategory::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('document_subcategory_id', null)),
                        Select::make('document_subcategory_id')
                            ->label('Subkategori')
                            ->options(function (Get $get) {
                                if (! $get('document_category_id')) {
                                    return [];
                                }

                                return DocumentSubcategory::where('document_category_id', $get('document_category_id'))
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->helperText('Opsional — hanya menampilkan subkategori dari kategori terpilih.'),

                        Select::make('document_type_id')
                            ->label('Jenis Dokumen')
                            ->options(fn () => DocumentType::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('unit_id')
                            ->label('Unit Kerja')
                            ->options(fn () => Unit::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live(),
                        Select::make('owner_id')
                            ->label('Pemilik Dokumen')
                            ->options(fn () => User::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->default(fn () => auth()->id()),
                        TextInput::make('year')
                            ->label('Tahun')
                            ->numeric()
                            ->required()
                            ->default(fn () => now()->year)
                            ->minValue(2000)
                            ->maxValue(2100),

                        TextInput::make('document_number')
                            ->label('Nomor Dokumen')
                            ->maxLength(255)
                            ->helperText('Kosongkan untuk diisi manual nanti, atau klik tombol generate.')
                            ->suffixAction(
                                Action::make('generate')
                                    ->icon('heroicon-m-sparkles')
                                    ->tooltip('Generate otomatis: DMS/{UNIT}/{KATEGORI}/{TAHUN}/{URUT}')
                                    ->action(function (Set $set, Get $get) {
                                        $unit = Unit::find($get('unit_id'));
                                        $category = DocumentCategory::find($get('document_category_id'));

                                        if (! $unit || ! $category || ! $get('year')) {
                                            return;
                                        }

                                        $set('document_number', app(DocumentNumberService::class)->generate($unit, $category, (int) $get('year')));
                                    })
                            ),
                        TextInput::make('document_code')
                            ->label('Kode Dokumen')
                            ->maxLength(255),

                        DatePicker::make('effective_date')
                            ->label('Tanggal Berlaku'),
                        DatePicker::make('expired_date')
                            ->label('Tanggal Kadaluarsa'),

                        Select::make('confidentiality_level')
                            ->label('Tingkat Akses')
                            ->options(collect(ConfidentialityLevel::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()]))
                            ->required()
                            ->default(ConfidentialityLevel::Internal->value)
                            ->helperText(fn (Get $get) => $get('confidentiality_level')
                                ? ConfidentialityLevel::from($get('confidentiality_level'))->description()
                                : null)
                            ->live(),
                    ]),

                Section::make('File Dokumen')
                    ->columns(1)
                    ->components([
                        Radio::make('source_type')
                            ->label('Sumber File')
                            ->options([
                                'upload' => 'Upload File',
                                'link' => 'Link / URL Eksternal',
                            ])
                            ->default('upload')
                            ->inline()
                            ->live()
                            ->helperText(fn (string $operation) => $operation === 'edit'
                                ? 'Pilih hanya jika ingin mengunggah versi berikutnya.'
                                : null),
                        FileUpload::make('file')
                            ->label('Upload File')
                            ->storeFiles(false)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            ])
                            ->maxSize(fn () => (int) config('documents.max_upload_size'))
                            ->visible(fn (Get $get) => $get('source_type') === 'upload')
                            ->required(fn (Get $get, string $operation) => $operation === 'create' && $get('source_type') === 'upload')
                            ->helperText(fn (string $operation) => $operation === 'create'
                                ? 'PDF, DOC(X), XLS(X), PPT(X). Maksimal '.round(config('documents.max_upload_size') / 1024, 1).' MB.'
                                : 'Upload file baru untuk membuat versi berikutnya (opsional).'),
                        TextInput::make('file_url')
                            ->label('URL File')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn (Get $get) => $get('source_type') === 'link')
                            ->required(fn (Get $get, string $operation) => $operation === 'create' && $get('source_type') === 'link')
                            ->helperText('Tautan langsung ke file (mis. PDF) yang sudah dipublikasikan di situs lain. Tidak dapat diverifikasi checksum-nya seperti file yang diunggah.'),
                        Textarea::make('change_notes')
                            ->label('Catatan Perubahan')
                            ->visibleOn('edit')
                            ->helperText('Diisi jika mengunggah file/link versi baru.'),
                    ]),

                Section::make('Persetujuan Cepat')
                    ->description('Opsional — hanya untuk Admin Dokumen/Approver yang berwenang.')
                    ->visible(fn (string $operation) => $operation === 'create'
                        && auth()->user()?->can('documents.approve')
                        && auth()->user()?->can('documents.publish'))
                    ->components([
                        Toggle::make('approve_and_publish')
                            ->label('Setujui & terbitkan langsung')
                            ->helperText('Lewati proses review — dokumen ini langsung berstatus Approved lalu Published begitu disimpan, seolah sudah Anda setujui dan terbitkan sendiri.')
                            ->default(false),
                    ]),
            ]);
    }
}
