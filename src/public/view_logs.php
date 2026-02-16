
<?php
$log = __DIR__ . '/../storage/errors.log';
$txt = file_exists($log) ? file_get_contents($log) : 'No errors logged.';
?><!doctype html><html><head><meta charset="utf-8"><title>Logs</title></head><body>
<h2>Errors Log</h2>
<pre><?php echo htmlspecialchars($txt); ?></pre>
<p><a href="dashboard.php">Back to dashboard</a></p>
</body></html>
