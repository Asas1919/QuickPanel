<?php
// abdullahsmm.store/admin.php
$ADMIN_PASS = 'asad'; // change this password

$file = __DIR__ . '/licensed-domains.json';
if (!file_exists($file)) {
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
}

// Load current list
function load_list($file) {
    $raw = @file_get_contents($file);
    $list = json_decode($raw, true);
    return is_array($list) ? $list : [];
}

// Save updated list
function save_list($file, $list) {
    $tmp = $file . '.tmp';
    if (file_put_contents($tmp, json_encode(array_values($list), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false) {
        rename($tmp, $file);
        return true;
    }
    return false;
}

// ===== Activation POST from client =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'record') {
    $domain = trim($_POST['domain'] ?? '');
    $note   = trim($_POST['note'] ?? '');
    if ($domain === '') { http_response_code(400); echo "Error: domain required."; exit; }

    if (!preg_match('/^[a-z0-9.-]+$/i', $domain)) { http_response_code(400); echo "Error: invalid domain."; exit; }

    $list = load_list($file);
    foreach ($list as $entry) {
        if (strcasecmp($entry['domain'], $domain) === 0) {
            echo "⚠️ This domain is already activated.";
            exit;
        }
    }

    $list[] = [
        'domain' => $domain,
        'note' => $note,
        'activated_at' => date('c')
    ];

    if (save_list($file, $list)) {
        echo "✅ Activated successfully for {$domain}";
    } else {
        http_response_code(500);
        echo "Error: could not save.";
    }
    exit;
}

// ===== Admin dashboard =====
session_start();
$pw = $_POST['pw'] ?? ($_SESSION['pw'] ?? '');
if (!empty($_POST['pw']) && $_POST['pw'] === $ADMIN_PASS) $_SESSION['pw'] = $pw;
if (isset($_POST['logout'])) { session_destroy(); header('Location: '.$_SERVER['PHP_SELF']); exit; }

if (isset($_POST['action']) && $_POST['action'] === 'remove' && ($_SESSION['pw'] ?? '') === $ADMIN_PASS) {
    $rem = $_POST['domain'] ?? '';
    $list = load_list($file);
    $list = array_filter($list, fn($e) => strcasecmp($e['domain'], $rem) !== 0);
    save_list($file, $list);
    $success = "Removed {$rem}";
}

$list = load_list($file);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Licensed Domains - Admin</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f6fb;padding:20px}
    .wrap{max-width:960px;margin:0 auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
    .danger{background:#ffecec;padding:10px;border-radius:6px}
    .success{background:#e6ffed;padding:10px;border-radius:6px}
    input[type=password]{padding:8px;border-radius:6px;border:1px solid #ddd}
    button{padding:8px 12px;border-radius:6px;border:0;background:#5f2eea;color:#fff;cursor:pointer}
  </style>
</head>
<body>
<div class="wrap">
  <h2>Licensed Domains - Admin</h2>
  <?php if (!empty($success)): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

  <?php if (empty($_SESSION['pw']) || $_SESSION['pw'] !== $ADMIN_PASS): ?>
    <form method="post"><input type="password" name="pw" placeholder="Admin password" required> <button type="submit">Unlock</button></form>
  <?php else: ?>
    <p><strong>Logged in as Admin</strong></p>
    <table>
      <thead><tr><th>Domain</th><th>Note</th><th>Activated At</th><th>Action</th></tr></thead>
      <tbody>
        <?php if (!$list): ?><tr><td colspan="4">No activations yet.</td></tr><?php endif; ?>
        <?php foreach ($list as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['domain']); ?></td>
            <td><?php echo htmlspecialchars($row['note']); ?></td>
            <td><?php echo htmlspecialchars($row['activated_at']); ?></td>
            <td>
              <form method="post" style="display:inline" onsubmit="return confirm('Remove this domain?');">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="domain" value="<?php echo htmlspecialchars($row['domain']); ?>">
                <button type="submit">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post"><button type="submit" name="logout" value="1" style="background:#888;margin-top:12px;">Lock</button></form>
  <?php endif; ?>
</div>
</body>
</html>
