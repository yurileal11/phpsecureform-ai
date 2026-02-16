<?php
// src/api/replay.php - Controlled replay of suggested mutations
require __DIR__ . '/../vendor/autoload.php';
use GuzzleHttp\Client;
use Dotenv\Dotenv;

header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

$allow = ($_ENV['ALLOW_REPLAY'] ?? 'false') === 'true';
if (!$allow) {
    http_response_code(403);
    echo json_encode([
        'error' => 'replay_disabled',
        'message' => 'Replay is disabled. Set ALLOW_REPLAY=true in .env to enable in a safe lab environment.'
    ]);
    exit;
}

$auth = $_SERVER['HTTP_X_PHSF_TOKEN'] ?? '';
$expected = $_ENV['PHSF_TOKEN'] ?? 'dev-token';
if ($auth !== $expected) {
    http_response_code(401);
    echo json_encode([
        'error' => 'unauthorized'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['intercept_file']) || empty($input['mutation'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'invalid_request',
        'message' => 'Provide intercept_file and mutation fields.'
    ]);
    exit;
}

$interceptFile = __DIR__ . '/../storage/' . basename($input['intercept_file']);
if (!file_exists($interceptFile)) {
    http_response_code(404);
    echo json_encode([
        'error' => 'not_found'
    ]);
    exit;
}

$intercept = json_decode(file_get_contents($interceptFile), true);
if (!$intercept) {
    http_response_code(500);
    echo json_encode([
        'error' => 'invalid_intercept'
    ]);
    exit;
}

// Build mutated request based on mutation descriptor (this is intentionally conservative)
$original = $intercept['request'] ?? [];
$mutation = $input['mutation'];

// Construct target URL (do not redirect to external hosts if not allowed; this is your lab responsibility)
$target = $original['url'] ?? '';
if (!$target) {
    http_response_code(400);
    echo json_encode([
        'error' => 'no_target_url'
    ]);
    exit;
}

$client = new Client([
    'timeout' => 30
]);
$options = ['headers'=>$original['headers'] ?? []];

// Apply simple param mutation: only supports form-encoded or query params in this PoC
$body = $original['body'] ?? '';
$headers = array_change_key_case($original['headers'] ?? [], CASE_LOWER);

if ($mutation['type'] === 'param') {
    parse_str($body, $parsed);
    $parsed[$mutation['param']] = $mutation['value'];
    $options['form_params'] = $parsed;
    $options['headers']['content-type'] = 'application/x-www-form-urlencoded';
    $resp = $client->request($original['method'] ?? 'POST', $target, $options);
    $status = $resp->getStatusCode();
    $respBody = substr((string)$resp->getBody(), 0, 2000);
    echo json_encode([
        'status' => $status,
        'body_preview' => $respBody
    ]);
    exit;
}

if ($mutation['type'] === 'file') {
    echo json_encode([
        'error' => 'file_mutation_not_performed',
        'message' => 'File mutations require manual upload within an isolated lab. See README.'
    ]);
    exit;
}

echo json_encode([
    'error' => 'unsupported_mutation'
]);