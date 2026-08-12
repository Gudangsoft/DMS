<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Storage Disk
    |--------------------------------------------------------------------------
    |
    | The filesystems.php disk used to store uploaded document files. Must
    | never be the "public" disk — see poin 10 / poin 30 of the spec.
    |
    */
    'storage_disk' => env('DOCUMENT_STORAGE_DISK', 'documents'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload Size (in kilobytes)
    |--------------------------------------------------------------------------
    */
    'max_upload_size' => (int) env('DOCUMENT_MAX_UPLOAD_SIZE', 20480),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Extensions
    |--------------------------------------------------------------------------
    */
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],

    /*
    |--------------------------------------------------------------------------
    | Document Number Format
    |--------------------------------------------------------------------------
    |
    | Placeholders: {UNIT} {CATEGORY} {YEAR} {NUMBER} — see poin 44.
    |
    */
    'number_format' => env('DOCUMENT_NUMBER_FORMAT', 'DMS/{UNIT}/{CATEGORY}/{YEAR}/{NUMBER}'),
];
