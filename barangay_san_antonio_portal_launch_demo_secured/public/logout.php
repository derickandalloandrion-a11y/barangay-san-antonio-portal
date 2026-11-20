<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/config/app.php';
require dirname(__DIR__) . '/app/Core/Security.php';

Security::sendSecurityHeaders($config);
Security::startSession($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed. Submit the sign-out form to continue.');
}

if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
    http_response_code(419);
    exit('Your session token expired. Refresh the page and try again.');
}

Security::destroySession();

$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/') {
    $base = '';
}
header('Location: ' . $base . '/');
exit;
