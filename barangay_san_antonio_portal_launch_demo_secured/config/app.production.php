<?php
declare(strict_types=1);
// Copy the approved values into config/app.php only after a valid TLS certificate
// and trusted public hostname have been configured by the hosting administrator.
return [
    'name' => 'Barangay San Antonio Portal',
    'base_url' => 'https://YOUR-APPROVED-DOMAIN.gov.ph',
    'timezone' => 'Asia/Manila',
    'session_name' => 'san_antonio_portal',
    'session_idle_timeout' => 1800,
    'session_absolute_lifetime' => 28800,
    'environment' => 'production',
    'debug' => false,
    'force_https' => true,
];
