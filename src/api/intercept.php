<?php
// src/api/intercept.php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/OpenAIClient.php';
require_once __DIR__ . '/../lib/Analyzer.php';
require_once __DIR__ . '/../lib/Mutator.php';
require_once __DIR__ . '/../lib/WAFDetector.php';

use PHPSecureForm\OpenAIClient;
use PHPSecureForm\Analyzer;
use Dotenv\Dotenv;

header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
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
if (!$input) {
    http_response_code(400);
    echo json_encode([
        'error' => 'invalid json'
    ]);
    exit;
}

// Basic truncation to avoid giant binary storage
if (isset($input['request']['body']) && strlen($input['request']['body']) > 1024*200) {
    $input['request']['body'] = '[TRUNCATED: ' . strlen($input['request']['body']) . ' bytes]';
}
if (isset($input['response']['body']) && strlen($input['response']['body']) > 1024*200) {
    $input['response']['body'] = '[TRUNCATED: ' . strlen($input['response']['body']) . ' bytes]';
}

$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) mkdir($storageDir, 0777, true);
$filename = $storageDir . '/intercept_' . time() . '_' . bin2hex(random_bytes(4)) . '.json';
file_put_contents($filename, json_encode($input, JSON_PRETTY_PRINT));

$analyzer = new Analyzer($input);
$summary = $analyzer->summarize();
file_put_contents($storageDir . '/summary_' . time() . '.json', json_encode($summary, JSON_PRETTY_PRINT));

$openaiKey = getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? '');
if ($openaiKey) {
    $client = new OpenAIClient($openaiKey);
    try {
        $ai = $client->analyzeIntercept($input, $summary);
        file_put_contents($storageDir . '/ai_' . time() . '.json', json_encode($ai, JSON_PRETTY_PRINT));
    } catch (Exception $e) {
        file_put_contents($storageDir . '/errors.log', $e->getMessage() . "\n", FILE_APPEND);
    }
}

echo json_encode([
    'status' => 'ok',
    'file' => $filename,
    'summary' => $summary
]);