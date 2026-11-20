<?php
declare(strict_types=1);

final class Security
{
    public static function sendSecurityHeaders(array $config = []): void
    {
        if (headers_sent()) return;
        $isHttps = self::isHttps();

        if (!empty($config['force_https']) && !$isHttps) {
            $baseUrl = (string)($config['base_url'] ?? '');
            $host = parse_url($baseUrl, PHP_URL_HOST);
            if ($host === null || $host === false || $host === '') {
                http_response_code(503);
                exit('HTTPS is required but the trusted HTTPS base URL is not configured.');
            }
            $port = parse_url($baseUrl, PHP_URL_PORT);
            $authority = $host . ($port !== null ? ':' . (int)$port : '');
            $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
            if ($uri === '' || $uri[0] !== '/' || str_starts_with($uri, '//')) $uri = '/';
            header('Location: https://' . $authority . $uri, true, 308);
            exit;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        // Inline styles are retained for compatibility with existing server-rendered pages.
        // Inline scripts remain disallowed; use external same-origin scripts.
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; img-src 'self' data:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'" . ($isHttps ? '; upgrade-insecure-requests' : ''));
        if ($isHttps) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    private static function isHttps(): bool
    {
        return isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off' && (string)$_SERVER['HTTPS'] !== '';
    }

    public static function startSession(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');

        $sessionName = (string)($config['session_name'] ?? 'san_antonio_portal');
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/', $sessionName)) {
            throw new RuntimeException('Invalid session cookie name configuration.');
        }
        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (!session_start()) throw new RuntimeException('Unable to start the security session.');
    }

    public static function establishAuthenticatedSession(int $userId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || $userId <= 0) throw new RuntimeException('Cannot establish an authenticated session.');
        $_SESSION = [];
        if (!session_regenerate_id(true)) throw new RuntimeException('Unable to regenerate the session identifier.');
        $now = time();
        $_SESSION['user_id'] = $userId;
        $_SESSION['_auth_started_at'] = $now;
        $_SESSION['_last_activity_at'] = $now;
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function enforceAuthenticatedSession(array $config): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) return false;
        $startedAt = $_SESSION['_auth_started_at'] ?? null;
        $lastActivityAt = $_SESSION['_last_activity_at'] ?? null;
        if (!is_int($startedAt) || !is_int($lastActivityAt)) { self::destroySession(); return false; }
        $now = time();
        $idleTimeout = max(1, (int)($config['session_idle_timeout'] ?? 1800));
        $absoluteLifetime = max(1, (int)($config['session_absolute_lifetime'] ?? 28800));
        if ($startedAt > $now + 60 || $lastActivityAt > $now + 60 || ($now - $lastActivityAt) >= $idleTimeout || ($now - $startedAt) >= $absoluteLifetime) {
            self::destroySession();
            return false;
        }
        $_SESSION['_last_activity_at'] = $now;
        return true;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || !preg_match('/^[a-f0-9]{64}$/', $_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function rotateCsrfToken(): string
    {
        unset($_SESSION['_csrf']);
        return self::csrfToken();
    }

    public static function verifyCsrf($token): bool
    {
        return is_string($token) && strlen($token) === 64 && ctype_xdigit($token)
            && isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function destroySession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool)($params['secure'] ?? false),
                'httponly' => (bool)($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
