
<?php
session_start();
// simple dashboard to list intercepted files and allow viewing and replaying (replay disabled unless ALLOW_REPLAY=true)
$storage = __DIR__ . '/../storage';
$files = [];
if (is_dir($storage)) {
    $iter = new DirectoryIterator($storage);
    foreach ($iter as $file) {
        if ($file->isFile() && strpos($file->getFilename(),'intercept_') === 0) {
            $files[] = $file->getFilename();
        }
    }
    rsort($files);
}
$allowReplay = (getenv('ALLOW_REPLAY') === 'true' || ($_ENV['ALLOW_REPLAY'] ?? '') === 'true');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>PHPSecureForm AI - Dashboard</title>
<style>
body{font-family: Arial, Helvetica, sans-serif;margin:20px;}
table{border-collapse: collapse;width:100%}
th,td{border:1px solid #ddd;padding:8px}
th{background:#f4f4f4}
.button{padding:6px 10px;border-radius:4px;border:none;cursor:pointer}
.button.primary{background:#007bff;color:#fff}
.button.warn{background:#dc3545;color:#fff}
</style>
</head>
<body>
<h2>PHPSecureForm AI — Dashboard</h2>
<p><a href="index.php">Home</a> | <a href="view_logs.php">Logs</a></p>
<p>Replay allowed: <strong><?php echo $allowReplay ? 'YES' : 'NO'; ?></strong></p>
<table>
<tr><th>Intercept file</th><th>Actions</th></tr>
<?php foreach($files as $f): ?>
  <tr>
    <td><?php echo htmlspecialchars($f); ?></td>
    <td>
      <a class="button" href="view.php?file=<?php echo urlencode($f); ?>">View</a>
      <?php if ($allowReplay): ?>
        <button class="button primary" onclick="startReplay('<?php echo addslashes($f); ?>')">Replay</button>
      <?php else: ?>
        <button class="button" disabled title="Enable ALLOW_REPLAY in .env to enable">Replay</button>
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
</table>

<script>
function startReplay(file) {
  let token = prompt('Enter X-PHSF-Token to authorize this replay (this token is defined in your .env):');
  if (!token) return;
  // fetch summary to get suggested mutations
  fetch('/api/get_summary.php?file=' + encodeURIComponent(file))
    .then(r=>r.json()).then(data=>{
      if (!data.summary) { alert('No summary available'); return; }
      let muts = data.summary.suggested_mutations || [];
      if (muts.length === 0) { alert('No suggested mutations found'); return; }
      // pick first mutation for demo; in full UI allow selecting
      let mutation = muts[0];
      if (!confirm('Replay first suggested mutation?\n' + JSON.stringify(mutation))) return;
      fetch('/api/replay.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-PHSF-Token': token},
        body: JSON.stringify({intercept_file: file, mutation: mutation})
      }).then(r=>r.json()).then(resp=>{
        alert('Replay response: ' + JSON.stringify(resp));
      }).catch(e=>alert('Replay error: ' + e));
    });
}
</script>
</body>
</html>
