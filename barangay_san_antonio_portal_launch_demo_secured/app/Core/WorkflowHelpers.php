<?php
declare(strict_types=1);
final class WorkflowHelpers
{
    public static function requireUser(PDO $db, array $config): array
    {
        if (!Security::enforceAuthenticatedSession($config)) { Security::destroySession(); header('Location: login.php'); exit; }
        $user=Authorization::user($db,(int)($_SESSION['user_id']??0));
        if (!$user) { Security::destroySession(); header('Location: login.php'); exit; }
        return $user;
    }
    public static function reference(string $prefix): string
    {
        return strtoupper($prefix).'-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
    }
    public static function clean(string $value, int $max): string
    {
        $value=trim($value);
        $length=function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value);
        if ($length>$max) throw new InvalidArgumentException('One of the submitted fields is too long.');
        return $value;
    }
    public static function validDate(string $date, bool $futureOnly=false): bool
    {
        $d=DateTime::createFromFormat('!Y-m-d',$date);
        if (!$d || $d->format('Y-m-d')!==$date) return false;
        return !$futureOnly || $d >= new DateTime('today');
    }
}
