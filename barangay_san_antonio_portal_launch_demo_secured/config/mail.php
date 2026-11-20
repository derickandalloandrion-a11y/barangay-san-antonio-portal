<?php
declare(strict_types=1);
/* Local-only mail preview. Replace with approved SMTP settings before public launch. */
return [
    'base_url' => 'http://localhost/barangay_san_antonio_portal_launch_demo_secured/public',
    'from_email' => 'no-reply@example.invalid',
    'from_name' => 'Barangay San Antonio Portal (Local Demo)',
    'driver' => 'file',
    'preview_directory' => dirname(__DIR__) . '/storage/mail-preview',
    'smtp' => [
        'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls',
        'username' => '', 'password' => '', 'timeout' => 20,
    ],
];
