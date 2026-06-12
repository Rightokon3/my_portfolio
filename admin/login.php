<?php
session_start();
require_once '../includes/config.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = clean($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u && $p) {
        $db   = Database::get();
        $user = $db->one("SELECT * FROM admin_users WHERE username=?", [$u]);
        if ($user && password_verify($p, $user['password_hash'])) {
            $_SESSION['admin_id']    = $user['id'];
            $_SESSION['admin_user']  = $user['username'];
            $_SESSION['last_active'] = time();
            $db->query("UPDATE admin_users SET last_login=NOW() WHERE id=?", [$user['id']]);
            logAction('Login');
            header('Location: dashboard.php'); exit;
        }
        $error = 'Invalid credentials. Try again.';
    } else { $error = 'Fill in both fields.'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Login — NFO_DEV</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
<style>
:root{--c1:#ff2d78;--c2:#00f5ff;--c3:#bf5fff;--dark:#04010a;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--dark);font-family:'Share Tech Mono',monospace;min-height:100vh;display:flex;align-items:center;justify-content:center;cursor:none;}
body::before{content:'';position:fixed;inset:0;pointer-events:none;background:repeating-linear-gradient(0deg,rgba(0,0,0,0.06) 0,rgba(0,0,0,0.06) 1px,transparent 1px,transparent 3px);}
.grid-bg{background-image:linear-gradient(rgba(191,95,255,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(191,95,255,0.04) 1px,transparent 1px);background-size:48px 48px;}
.card{background:rgba(10,4,20,0.92);border:1px solid rgba(191,95,255,0.2);position:relative;overflow:hidden;border-radius:4px;}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--c1),var(--c2),transparent);opacity:0.7;}
.card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--c3),transparent);opacity:0.5;}
.scan{position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,rgba(0,245,255,0.4),transparent);animation:scan 4s linear infinite;}
@keyframes scan{0%{top:0}100%{top:100%}}
.inp{width:100%;background:rgba(4,1,10,0.8);border:1px solid rgba(191,95,255,0.2);color:#e8d5ff;padding:12px 14px;font-family:'Share Tech Mono',monospace;font-size:13px;outline:none;transition:all 0.3s;clip-path:polygon(4px 0%,100% 0%,calc(100% - 4px) 100%,0% 100%);}
.inp::placeholder{color:rgba(232,213,255,0.18);}
.inp:focus{border-color:var(--c2);box-shadow:0 0 16px rgba(0,245,255,0.15);}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,var(--c1),var(--c3));color:#fff;font-family:'Orbitron',sans-serif;font-size:12px;font-weight:700;letter-spacing:0.18em;border:none;cursor:none;transition:all 0.3s;clip-path:polygon(6px 0%,100% 0%,calc(100% - 6px) 100%,0% 100%);}
.btn:hover{box-shadow:0 0 35px rgba(255,45,120,0.5);transform:translateY(-1px);}
.orb{font-family:'Orbitron',sans-serif;}
#cur{position:fixed;width:10px;height:10px;border-radius:50%;background:var(--c1);pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:difference;}
</style>
</head>
<body class="grid-bg">
<div id="cur"></div>
<script>document.addEventListener('mousemove',e=>{const c=document.getElementById('cur');c.style.left=e.clientX+'px';c.style.top=e.clientY+'px';});</script>

<div class="card" style="width:100%;max-width:420px;margin:24px;padding:44px 36px;">
  <div class="scan"></div>

  <div style="text-align:center;margin-bottom:36px;">
    <div class="orb" style="font-size:2rem;font-weight:900;color:var(--c1);text-shadow:0 0 20px rgba(255,45,120,0.5);letter-spacing:0.06em;">NFO_DEV</div>
    <div style="font-size:10px;letter-spacing:0.3em;color:rgba(191,95,255,0.5);margin-top:6px;">ADMIN ACCESS // SECURE ZONE</div>
    <div style="width:60px;height:1px;background:linear-gradient(90deg,transparent,var(--c3),transparent);margin:14px auto 0;"></div>
  </div>

  <?php if($error): ?>
  <div style="margin-bottom:18px;padding:11px 14px;background:rgba(255,45,120,0.08);border:1px solid rgba(255,45,120,0.25);color:#ff2d78;font-size:12px;text-align:center;clip-path:polygon(4px 0%,100% 0%,calc(100% - 4px) 100%,0% 100%);">
    ⚠ <?=htmlspecialchars($error)?>
  </div>
  <?php endif; ?>

  <form method="POST" style="display:flex;flex-direction:column;gap:18px;">
    <div>
      <div style="font-size:10px;letter-spacing:0.2em;color:rgba(191,95,255,0.6);margin-bottom:7px;">// USERNAME</div>
      <input class="inp" type="text" name="username" placeholder="admin" value="<?=htmlspecialchars($_POST['username']??'')?>" required autocomplete="username">
    </div>
    <div>
      <div style="font-size:10px;letter-spacing:0.2em;color:rgba(191,95,255,0.6);margin-bottom:7px;">// PASSWORD</div>
      <input class="inp" type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn" style="margin-top:6px;">AUTHENTICATE →</button>
  </form>

  <div style="margin-top:28px;text-align:center;">
    <a href="../index.html" style="font-size:11px;letter-spacing:0.15em;color:rgba(0,245,255,0.3);text-decoration:none;transition:color 0.3s;">← RETURN TO PORTFOLIO</a>
  </div>
  <div style="margin-top:12px;text-align:center;font-size:10px;color:rgba(191,95,255,0.2);">Default: admin / password</div>
</div>
</body>
</html>
