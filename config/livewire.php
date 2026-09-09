<?php

return [

    'class_namespace' => 'App\\Livewire',

    'view_path' => resource_path('views/livewire'),

    'layout' => 'components.layouts.app',

    'lazy_placeholder' => null,

    /*
    |---------------------------------------------------------------------------
    | Temporary File Uploads
    |---------------------------------------------------------------------------
    |
    | disk is forced to 'local' regardless of FILESYSTEM_DISK (which is
    | 'proofs' — S3/MinIO — everywhere else in this app, for proof documents).
    | Left as the package default (null → 'default'/FILESYSTEM_DISK), a
    | Filament FileUpload's preview thumbnail during upload generates a
    | presigned S3 URL against AWS_ENDPOINT (http://minio:9000 — the
    | docker-compose internal hostname), which no real browser can resolve
    | and which HTTPS pages block outright as mixed content. 'local' isn't
    | web-served at all; Livewire proxies its preview through its own route
    | instead of a presigned URL, so this sidesteps the problem entirely
    | rather than trading it for "proof documents sit in a public bucket
    | for a few seconds" (ARCHITECTURE.md §7: no public bucket for proofs,
    | ever). Filament moves the file to the field's own ->disk() on save —
    | this only governs the few seconds before that.
    |
    */

    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

    'render_on_redirect' => false,

    'legacy_model_binding' => false,

    'inject_assets' => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],

    'inject_morph_markers' => true,

    'smart_wire_keys' => false,

    'pagination_theme' => 'tailwind',

    'release_token' => 'a',
];
