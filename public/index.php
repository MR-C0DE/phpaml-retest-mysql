<?php

declare(strict_types=1);

const APP_VERSION = 'v2';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: 'localhost', getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE') ?: 'retest'),
    getenv('DB_USERNAME') ?: 'root',
    getenv('DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);

$path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($path === '/api/health') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['project' => 'phpaml-retest-mysql', 'status' => 'ok', 'version' => APP_VERSION, 'database' => (int) $pdo->query('SELECT 1')->fetchColumn()], JSON_THROW_ON_ERROR);
    exit;
}

if ($path === '/api/notes' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $body = trim((string) ($payload['body'] ?? ''));
    if ($body === '' || strlen($body) > 255) {
        http_response_code(422);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'invalid_body'], JSON_THROW_ON_ERROR);
        exit;
    }
    $statement = $pdo->prepare('INSERT INTO notes (body) VALUES (:body)');
    $statement->execute(['body' => $body]);
    http_response_code(201);
}

if ($path === '/api/notes') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['version' => APP_VERSION, 'notes' => $pdo->query('SELECT id, body, created_at FROM notes ORDER BY id')->fetchAll()], JSON_THROW_ON_ERROR);
    exit;
}

$count = (int) $pdo->query('SELECT COUNT(*) FROM notes')->fetchColumn();
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MySQL persistence test</title></head><body><main><small>MYSQL PERSISTENCE · <?= APP_VERSION ?></small><h1>Database survived the redeploy.</h1><p><?= $count ?> persistent note(s).</p></main></body></html>
