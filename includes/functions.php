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

function normalize_user_role(?string $role): string
{
    $role = strtolower(trim((string)$role));

    if ($role === 'user') {
        return 'member';
    }

    return in_array($role, ['admin', 'coach', 'member'], true) ? $role : 'member';
}

function current_user_role(): string
{
    return normalize_user_role($_SESSION['role'] ?? 'member');
}

function current_user_role_label(): string
{
    return [
        'admin' => 'Admin',
        'coach' => 'Antrenor',
        'member' => 'Membru',
    ][current_user_role()] ?? 'Membru';
}

function user_has_role(string $role): bool
{
    return current_user_role() === $role;
}

function user_has_any_role(array $roles): bool
{
    return in_array(current_user_role(), $roles, true);
}

function is_admin(): bool
{
    return user_has_role('admin');
}

function is_coach(): bool
{
    return user_has_role('coach');
}

function is_member(): bool
{
    return user_has_role('member');
}

function can_manage_activities(): bool
{
    return user_has_any_role(['admin', 'coach']);
}

function can_manage_competition_results(): bool
{
    return user_has_any_role(['admin', 'coach']);
}

function can_manage_admin_data(): bool
{
    return is_admin();
}

function current_member_id(): int
{
    return (int)($_SESSION['member_id'] ?? 0);
}

function current_coach_id(): int
{
    return (int)($_SESSION['coach_id'] ?? 0);
}

function role_guard(bool $allowed, string $message = 'Nu ai permisiunea necesară pentru această acțiune.'): void
{
    if (!$allowed) {
        $_SESSION['flash_error'] = $message;
        redirect_to('index.php');
    }
}

function take_flash_error(): string
{
    $message = $_SESSION['flash_error'] ?? '';
    unset($_SESSION['flash_error']);

    return $message;
}

function role_read_only_notice(string $area): string
{
    if (is_admin()) {
        return '';
    }

    return 'Ești autentificat ca ' . current_user_role_label() . '. Pentru ' . $area . ', ai acces de vizualizare.';
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

function import_uploaded_csv(string $fieldName, callable $handleRow): array
{
    $result = [
        'imported' => 0,
        'skipped' => 0,
        'invalid' => 0,
        'missing_reference' => 0,
        'error' => '',
    ];

    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Te rugăm să alegi un fișier CSV valid.';
        return $result;
    }

    $handle = fopen($_FILES[$fieldName]['tmp_name'], 'r');

    if ($handle === false) {
        $result['error'] = 'Eroare la deschiderea fișierului CSV.';
        return $result;
    }

    $headers = fgetcsv($handle, 10000, ',');

    if ($headers === false) {
        fclose($handle);
        $result['error'] = 'Fișierul CSV este gol.';
        return $result;
    }

    $headers = array_map(static function ($header): string {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header);

        return strtolower(trim($header, " \t\n\r\0\x0B\"'"));
    }, $headers);

    while (($data = fgetcsv($handle, 10000, ',')) !== false) {
        if (count(array_filter($data, static fn($value) => trim((string)$value) !== '')) === 0) {
            continue;
        }

        $row = [];

        foreach ($headers as $index => $header) {
            if ($header !== '') {
                $row[$header] = trim((string)($data[$index] ?? ''));
            }
        }

        $status = $handleRow($row);

        if (isset($result[$status])) {
            $result[$status]++;
        } else {
            $result['invalid']++;
        }
    }

    fclose($handle);

    return $result;
}

function csv_import_summary(string $entityPlural, array $result): string
{
    if ($result['error'] !== '') {
        return $result['error'];
    }

    $message = "Rânduri importate pentru {$entityPlural}: {$result['imported']}.";

    if ($result['skipped'] > 0) {
        $message .= " Rânduri deja existente omise: {$result['skipped']}.";
    }

    if ($result['missing_reference'] > 0) {
        $message .= " Rânduri omise din cauza unor referințe inexistente: {$result['missing_reference']}.";
    }

    if ($result['invalid'] > 0) {
        $message .= " Rânduri invalide omise: {$result['invalid']}.";
    }

    return $message;
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
