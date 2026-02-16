
<?php
header('Content-Type: application/json');
$file = $_GET['file'] ?? '';
$path = __DIR__ . '/../storage/' . basename($file);
if (!file_exists($path)) { echo json_encode(['error'=>'not_found']); exit; }
$intercept = json_decode(file_get_contents($path), true);
$dir = dirname($path);
$it = new DirectoryIterator($dir);
$best = null;
foreach ($it as $f) {
    if (strpos($f->getFilename(),'summary_') === 0) {
        $s = json_decode(file_get_contents($dir . '/' . $f->getFilename()), true);
        if (isset($s['url']) && isset($intercept['request']['url']) && $s['url'] === $intercept['request']['url']) {
            $best = $s;
            break;
        }
    }
}
echo json_encode(['summary'=>$best]);