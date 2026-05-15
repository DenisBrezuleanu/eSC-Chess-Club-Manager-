<?php

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_field(): string
{
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

function selected_attr($actual, $expected): string
{
    return (string)$actual === (string)$expected ? ' selected' : '';
}

function is_active_page($matches): bool
{
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $matches = is_array($matches) ? $matches : [$matches];

    return in_array($current, $matches, true);
}

function format_money($amount): string
{
    return number_format((float)$amount, 2, '.', '');
}

function app_base_url(): string
{
    if (empty($_SERVER['HTTP_HOST'])) {
        return 'http://localhost/eSC-Chess-Club-Manager-';
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $scriptDir = rtrim($scriptDir, '/');

    if (basename($scriptDir) === 'api') {
        $scriptDir = rtrim(dirname($scriptDir), '/');
    }

    if ($scriptDir === '.' || $scriptDir === '/') {
        $scriptDir = '';
    }

    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $scriptDir;
}

function redirect_to(string $url): void
{
    header('Location: ' . $url);
    exit();
}
