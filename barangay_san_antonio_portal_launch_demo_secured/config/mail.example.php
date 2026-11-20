<?php
declare(strict_types=1);
/* Local demo defaults to file preview. Switch driver to smtp only after Gmail App Password setup. */
return [
    'base_url' => 'http://localhost/barangay_san_antonio_portal_launch_demo/public',
    'from_email' => 'no-reply@example.invalid',
    'from_name' => 'Barangay San Antonio Portal (Demo)',
    'driver' => 'file', // file | smtp
    'preview_directory' => dirname(__DIR__) . '/storage/mail-preview',
    'smtp' => [
        'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls',
        'username' => 'your-gmail-address@gmail.com',
        'password' => 'PASTE_GOOGLE_APP_PASSWORD_HERE', 'timeout' => 20,
    ],
];
