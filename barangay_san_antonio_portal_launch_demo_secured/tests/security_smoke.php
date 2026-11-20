<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Core/Security.php';
require dirname(__DIR__) . '/app/Core/View.php';
$checks = 0;
$assert = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
    $checks++;
};
$assert(Security::e('<script>"x"</script>') === '&lt;script&gt;&quot;x&quot;&lt;/script&gt;', 'HTML escaping');
$assert(Security::e(null) === '', 'null output escaping');
$assert(!Security::verifyCsrf(str_repeat('a', 64)), 'CSRF rejects missing session secret');
$assert(!Security::verifyCsrf(['bad']), 'CSRF rejects non-string input');
try { View::render('../config/database'); $assert(false, 'view traversal blocked'); }
catch (InvalidArgumentException $e) { $assert(true, 'view traversal blocked'); }
$assert(method_exists(Security::class, 'enforceAuthenticatedSession'), 'session lifetime guard exists');
$assert(method_exists(Security::class, 'establishAuthenticatedSession'), 'authenticated session rotation exists');
echo "PASS: {$checks} security smoke checks.\n";
