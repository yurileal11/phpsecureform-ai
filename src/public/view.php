
<?php
$file = $_GET['file'] ?? '';
$path = __DIR__ . '/../storage/' . basename($file);
if (!file_exists($path)) {
    echo 'File not found';
    exit;
}
$content = file_get_contents($path);
$json = json_decode($content, true);
// find best summary by url match
$summary = null;
$dir = dirname($path);
$it = new DirectoryIterator($dir);
foreach ($it as $f) {
    if (strpos($f->getFilename(),'summary_') === 0) {
        $s = json_decode(file_get_contents($dir . '/' . $f->getFilename()), true);
        if (isset($s['url']) && isset($json['request']['url']) && $s['url'] === $json['request']['url']) {
            $summary = $s;
            break;
        }
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>View Intercept</title></head><body>
<h2>Intercept: <?php echo htmlspecialchars(basename($path)); ?></h2>
<h3>Request</h3>
<pre><?php echo htmlspecialchars(json_encode($json['request'] ?? [], JSON_PRETTY_PRINT)); ?></pre>
<h3>Response</h3>
<pre><?php echo htmlspecialchars(json_encode($json['response'] ?? [], JSON_PRETTY_PRINT)); ?></pre>
<?php if ($summary): ?>
  <h3>Summary</h3>
  <pre><?php echo htmlspecialchars(json_encode($summary, JSON_PRETTY_PRINT)); ?></pre>
<?php else: ?>
  <p>No local summary found for this intercept.</p>
<?php endif; ?>
<p><a href="dashboard.php">Back to dashboard</a></p>
</body></html>
