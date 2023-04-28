<?php

declare(strict_types=1);

if (! function_exists('dd')) {
    function dd(mixed $vars): void
    {
        $out = '';
        if (gettype($vars) === 'array') {
            $out = implode(' ', $vars);
        }

        if (gettype($vars) === 'string') {
            $out = $vars;
        }

        if (is_cli()) {
            fwrite(STDOUT, $out);
            die;
        }

        echo '<pre>';
        var_dump($vars);
        echo '</pre>';

        die;
    }
}

if (! function_exists('is_cli')) {
    function is_cli(): bool
    {
        return PHP_SAPI === 'cli';
    }
}
