<?php
declare(strict_types=1);

final class View
{
    public static function render(string $view, array $data = []): void
    {
        if (!preg_match('/^[A-Za-z0-9_\/-]+$/', $view) || str_contains($view, '..')) {
            throw new InvalidArgumentException('Invalid view identifier.');
        }
        $root = realpath(dirname(__DIR__, 2) . '/app/Views');
        $file = realpath(dirname(__DIR__, 2) . '/app/Views/' . $view . '.php');
        if ($root === false || $file === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) {
            throw new RuntimeException('Requested view is unavailable.');
        }
        extract($data, EXTR_SKIP);
        require $file;
    }
}
