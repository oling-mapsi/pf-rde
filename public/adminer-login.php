<?php

declare(strict_types=1);

/**
 * Adminer bootstrap helper.
 *
 * - Reuses DATABASE_URL from runtime env when available.
 * - Falls back to .env.local then .env for local/dev usage.
 * - Redirects to Adminer with prefilled server/user/database.
 */
function loadDatabaseUrl(): ?string
{
    $envUrl = getenv('DATABASE_URL');
    if (is_string($envUrl) && $envUrl !== '') {
        return $envUrl;
    }

    $projectRoot = dirname(__DIR__);
    $candidateFiles = [
        $projectRoot . '/.env.local',
        $projectRoot . '/.env',
    ];

    foreach ($candidateFiles as $file) {
        if (!is_file($file)) {
            continue;
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            continue;
        }

        foreach ($lines as $line) {
            if (str_starts_with(ltrim($line), '#')) {
                continue;
            }

            if (!preg_match('/^\s*DATABASE_URL\s*=\s*(.+)\s*$/', $line, $matches)) {
                continue;
            }

            $value = trim($matches[1]);
            $first = $value[0] ?? '';
            $last = $value[strlen($value) - 1] ?? '';
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }

            return stripcslashes($value);
        }
    }

    return null;
}

function badConfig(string $message): never
{
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Adminer configuration error: {$message}\n";
    exit(1);
}

$databaseUrl = loadDatabaseUrl();
if ($databaseUrl === null) {
    badConfig('DATABASE_URL introuvable.');
}

$parts = parse_url($databaseUrl);
if ($parts === false) {
    badConfig('DATABASE_URL invalide.');
}

$scheme = strtolower((string) ($parts['scheme'] ?? ''));
if (!in_array($scheme, ['postgres', 'postgresql'], true)) {
    badConfig("Seul PostgreSQL est supporté, schema detecte: {$scheme}");
}

$host = (string) ($parts['host'] ?? '127.0.0.1');
$port = isset($parts['port']) ? (string) $parts['port'] : '5432';
$username = (string) ($parts['user'] ?? '');
$database = ltrim((string) ($parts['path'] ?? ''), '/');

if ($username === '' || $database === '') {
    badConfig('Utilisateur ou base manquante dans DATABASE_URL.');
}

$query = http_build_query([
    'pgsql' => $host . ':' . $port,
    'username' => $username,
    'db' => $database,
]);

header('Location: /adminer.php?' . $query, true, 302);
exit;
