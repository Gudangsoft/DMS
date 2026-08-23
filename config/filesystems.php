<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        // 'local_no_finfo' (registered in AppServiceProvider) instead of the
        // stock 'local' driver on every local disk below: Flysystem's Local
        // adapter builds a Finfo-backed mime detector in its constructor
        // unconditionally, so on a host without ext-fileinfo even a Livewire
        // temp file upload crashes before this app's own code runs. See the
        // comment on Storage::extend('local_no_finfo', ...) for the full
        // reasoning — this app already trusts a known file-extension
        // allowlist over content-sniffed mime types anyway.
        'local' => [
            'driver' => 'local_no_finfo',
            '_disk_name' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        // Dedicated disk for document files (poin 10): never public, never served by
        // Laravel's built-in local-disk route — the only way to reach a file is
        // through SecureDownloadController, which enforces DocumentPolicy first.
        'documents' => [
            'driver' => 'local_no_finfo',
            '_disk_name' => 'documents',
            'root' => storage_path('app/private/documents'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local_no_finfo',
            '_disk_name' => 'public',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
