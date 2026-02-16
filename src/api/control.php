
<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
$storage = __DIR__ . '/../storage/control.json';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    if (!file_exists($storage)) {
        file_put_contents($storage, json_encode(['enabled'=>true,'allow_replay'=>false]));
    }
    echo file_get_contents($storage);
    exit;
}
if ($method === 'POST') {
    // change state - require token
    $token = $_SERVER['HTTP_X_PHSF_TOKEN'] ?? null;
    if (!$token) {
        http_response_code(401); echo json_encode(['error'=>'missing_token']); exit;
    }
    $expected = $_ENV['PHSF_TOKEN'] ?? 'dev-token';
    if ($token !== $expected) { 
        http_response_code(401); 
        echo json_encode(['error'=>'unauthorized']); 
        exit; 
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { 
        http_response_code(400); 
        echo json_encode(['error'=>'invalid_json']); 
        exit; 
    }
    $state = file_exists($storage) ? json_decode(file_get_contents($storage), true) : ['enabled'=>true,'allow_replay'=>false];
    $state = array_merge($state, $input);
    file_put_contents($storage, json_encode($state, JSON_PRETTY_PRINT));
    echo json_encode(['status'=>'ok','new'=>$state]);
    exit;
}
http_response_code(405);
