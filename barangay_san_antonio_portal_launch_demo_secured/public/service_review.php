<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Core/Security.php';
Security::sendSecurityHeaders($config);
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo 'This legacy endpoint is retired. Use the role-aware workflow_review.php endpoint from the dashboard.';
