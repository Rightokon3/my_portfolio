<?php
session_start();
require_once '../includes/config.php';
requireAuth();
$db = Database::get();

$totalContacts   = $db->count('contacts');
$unread          = $db->count('contacts','status=?',['unread']);
$totalBookings   = $db->count('bookings');
$pendingB        = $db->count('bookings','status=?',['pending']);
$confirmedB      = $db->count('bookings','status=?',['confirmed']);
$completedB      = $db->count('bookings','status=?',['completed']);

$recentContacts  = $db->all("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 6");
$recentBookings  = $db->all("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5");
$activityLog     = $db->all("SELECT al.*,au.username FROM activity_log al LEFT JOIN admin_users au ON al.admin_id=au.id ORDER BY al.created_at DESC LIMIT 12");
$allContacts     = $db->all("SELECT * FROM contacts ORDER BY created_at DESC");
$allBookings     = $db->all("SELECT * FROM bookings ORDER BY created_at DESC");
$settings        = $db->all("SELECT * FROM settings"); $smap=[]; foreach($settings as $s) $smap[$s['setting_key']]=$s['setting_value'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard — NFO_DEV</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--c1:#ff2d78;--c2:#00f5ff;--c3:#bf5fff;--c4:#ffe600;--dark:#04010a;--dark2:#0a0414;--panel:rgba(10,4,20,0.9);--text:#e8d5ff;--td:rgba(232,213,255,0.45);--br:rgba(191,95,255,0.18);}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--dark);color:var(--text);font-family:'Rajdhani',sans-serif;display:flex;height:100vh;overflow:hidden;}
.mono{font-family:'Share Tech Mono',monospace;}
.orb{font-family:'Orbitron',sans-serif;}
/* Scanlines */
body::before{content:'';position:fixed;inset:0;pointer-events:none;background:repeating-linear-gradient(0deg,rgba(0,0,0,0.04) 0,rgba(0,0,0,0.04) 1px,transparent 1px,transparent 3px);z-index:0;}
/* Sidebar */
#sidebar{width:220px;flex-shrink:0;background:rgba(4,1,10,0.98);border-right:1px solid rgba(255,45,120,0.12);display:flex;flex-direction:column;z-index:2;overflow-y:auto;}
#sidebar::after{content:'';position:absolute;top:0;left:220px;width:1px;height:100%;background:linear-gradient(to bottom,transparent,var(--c1),transparent);opacity:0.3;pointer-events:none;}
.nav-item{display:block;padding:9px 16px;font-family:'Share Tech Mono',monospace;font-size:11px;letter-spacing:0.15em;color:rgba(232,213,255,0.35);text-decoration:none;transition:all 0.2s;border-left:2px solid transparent;cursor:pointer;}
.nav-item:hover,.nav-item.active{color:var(--c2);border-left-color:var(--c2);background:rgba(0,245,255,0.04);}
/* Main */
#main{flex:1;overflow-y:auto;z-index:1;display:flex;flex-direction:column;}
/* Cards */
.card{background:var(--panel);border:1px solid var(--br);border-radius:4px;position:relative;}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--c3),transparent);opacity:0.45;}
/* Stat card */
.stat-card{background:linear-gradient(135deg,rgba(10,4,20,0.9),rgba(18,8,32,0.9));border:1px solid var(--br);border-radius:4px;padding:20px;position:relative;overflow:hidden;}
.stat-card::after{content:'';position:absolute;bottom:0;right:0;width:60px;height:60px;background:radial-gradient(circle,var(--glow) 0%,transparent 70%);opacity:0.15;}
/* Badges */
.b-unread{background:rgba(255,45,120,0.14);color:var(--c1);font-family:'Share Tech Mono',monospace;font-size:10px;padding:2px 7px;border-radius:2px;}
.b-pending{background:rgba(255,230,0,0.12);color:var(--c4);}
.b-confirmed{background:rgba(0,245,255,0.12);color:var(--c2);}
.b-declined{background:rgba(255,45,120,0.12);color:var(--c1);}
.b-completed{background:rgba(191,95,255,0.14);color:var(--c3);}
.b-read{background:rgba(232,213,255,0.08);color:rgba(232,213,255,0.4);}
.b-archived{background:rgba(100,100,100,0.15);color:#666;}
.badge{font-family:'Share Tech Mono',monospace;font-size:10px;padding:2px 7px;border-radius:2px;}
/* Table */
table{width:100%;border-collapse:collapse;}
th{font-family:'Share Tech Mono',monospace;font-size:10px;letter-spacing:0.2em;color:rgba(232,213,255,0.3);font-weight:normal;padding:10px 12px;text-align:left;border-bottom:1px solid rgba(191,95,255,0.12);}
td{padding:10px 12px;font-size:13px;border-bottom:1px solid rgba(191,95,255,0.07);vertical-align:middle;}
tr:hover td{background:rgba(191,95,255,0.03);}
/* Action buttons */
.ab{padding:3px 10px;border-radius:2px;font-family:'Share Tech Mono',monospace;font-size:10px;cursor:pointer;border:1px solid;transition:all 0.2s;background:transparent;}
/* Forms */
.fi{width:100%;background:rgba(4,1,10,0.9);border:1px solid rgba(191,95,255,0.18);color:var(--text);padding:10px 12px;font-family:'Share Tech Mono',monospace;font-size:12px;outline:none;transition:all 0.3s;}
.fi:focus{border-color:var(--c2);box-shadow:0 0 12px rgba(0,245,255,0.12);}
.fl{font-family:'Share Tech Mono',monospace;font-size:10px;letter-spacing:0.18em;color:rgba(191,95,255,0.55);display:block;margin-bottom:6px;}
/* Button */
.btn{padding:10px 20px;font-family:'Orbitron',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.14em;border:none;cursor:pointer;clip-path:polygon(5px 0%,100% 0%,calc(100% - 5px) 100%,0% 100%);transition:all 0.25s;}
.btn-p{background:linear-gradient(135deg,var(--c1),var(--c3));color:#fff;}
.btn-p:hover{box-shadow:0 0 28px rgba(255,45,120,0.45);}
.btn-g{background:transparent;border:1px solid var(--c2);color:var(--c2);}
.btn-g:hover{background:rgba(0,245,255,0.07);}
/* Scrollbar */
::-webkit-scrollbar{width:3px;}::-webkit-scrollbar-track{background:var(--dark);}::-webkit-scrollbar-thumb{background:var(--c3);border-radius:2px;}
/* Sections */
.sec{display:none;padding:28px;flex-direction:column;gap:22px;}
.sec.active{display:flex;}
/* Pulse dot */
.pd{width:7px;height:7px;border-radius:50%;display:inline-block;background:var(--c2);box-shadow:0 0 8px var(--c2);animation:pulse2 2s infinite;}
@keyframes pulse2{0%,100%{opacity:1;}50%{opacity:0.3;}}
/* Toast */
#toast{position:fixed;bottom:22px;right:22px;z-index:9999;background:var(--dark2);border:1px solid var(--c3);padding:12px 18px;font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--c2);transform:translateY(60px);opacity:0;transition:all 0.3s;clip-path:polygon(5px 0%,100% 0%,calc(100% - 5px) 100%,0% 100%);}
#toast.show{transform:translateY(0);opacity:1;}
/* Modal */
#modal{position:fixed;inset:0;background:rgba(4,1,10,0.92);z-index:8000;display:none;align-items:center;justify-content:center;}
#modal.open{display:flex;}
#modal-inner{background:rgba(10,4,20,0.98);border:1px solid rgba(255,45,120,0.25);max-width:540px;width:90%;max-height:80vh;overflow-y:auto;padding:32px;border-radius:4px;position:relative;}
#modal-inner::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--c1),transparent);}
</style>
</head>
<body>
<!-- Sidebar -->
<aside id="sidebar">
  <div style="padding:22px 18px 18px;border-bottom:1px solid rgba(255,45,120,0.1);">
    <div class="orb" style="font-size:1.1rem;font-weight:900;color:var(--c1);text-shadow:0 0 14px rgba(255,45,120,0.5);">NFO_DEV</div>
    <div class="mono" style="font-size:9px;color:rgba(191,95,255,0.35);letter-spacing:0.25em;margin-top:3px;">CONTROL PANEL</div>
  </div>

  <div style="padding:14px 16px;border-bottom:1px solid rgba(255,45,120,0.07);">
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--c1),var(--c3));display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:12px;font-weight:900;color:#fff;flex-shrink:0;"><?=strtoupper(substr($_SESSION['admin_user'],0,1))?></div>
      <div>
        <div class="mono" style="font-size:12px;color:var(--text);"><?=htmlspecialchars($_SESSION['admin_user'])?></div>
        <div style="display:flex;align-items:center;gap:5px;"><span class="pd"></span><span class="mono" style="font-size:9px;color:rgba(0,245,255,0.4);">ONLINE</span></div>
      </div>
    </div>
  </div>

  <nav style="padding:12px 8px;flex:1;">
    <div class="mono" style="font-size:9px;color:rgba(255,45,120,0.35);letter-spacing:0.25em;padding:6px 10px 10px;">// MENU</div>
    <a class="nav-item active" id="nb-dash"    onclick="go('dash')">◈ DASHBOARD</a>
    <a class="nav-item"        id="nb-msgs"    onclick="go('msgs')">◈ MESSAGES <span class="badge b-unread" style="float:right;margin-top:1px;"><?=$unread?></span></a>
    <a class="nav-item"        id="nb-bookings" onclick="go('bookings')">◈ BOOKINGS <span class="badge b-pending" style="float:right;margin-top:1px;"><?=$pendingB?></span></a>
    <a class="nav-item"        id="nb-analytics" onclick="go('analytics')">◈ ANALYTICS</a>
    <a class="nav-item"        id="nb-settings"  onclick="go('settings')">◈ SETTINGS</a>
    <div class="mono" style="font-size:9px;color:rgba(255,45,120,0.35);letter-spacing:0.25em;padding:16px 10px 8px;">// ACTIONS</div>
    <a class="nav-item" href="../index.html" target="_blank">↗ VIEW SITE</a>
    <a class="nav-item" href="logout.php" style="color:rgba(255,45,120,0.45);">✕ LOGOUT</a>
  </nav>
</aside>

<!-- Main -->
<div id="main">
  <!-- Header bar -->
  <header style="background:rgba(4,1,10,0.95);border-bottom:1px solid rgba(255,45,120,0.1);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;position:sticky;top:0;z-index:10;">
    <div>
      <div class="orb" id="page-title" style="font-size:1rem;color:var(--c2);">DASHBOARD</div>
      <div class="mono" style="font-size:9px;color:rgba(191,95,255,0.35);margin-top:2px;"><?=date('D d M Y // H:i T')?></div>
    </div>
    <?php if($unread>0): ?>
    <div class="badge b-unread" style="padding:5px 12px;"><?=$unread?> UNREAD MSG<?=$unread>1?'S':''?></div>
    <?php endif; ?>
  </header>

  <!-- ── DASHBOARD ── -->
  <div id="sec-dash" class="sec active">
    <!-- Stat row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
      <?php
      $cards=[
        ['MESSAGES',$totalContacts,"$unread unread",'var(--c1)'],
        ['BOOKINGS',$totalBookings,"$pendingB pending",'var(--c4)'],
        ['CONFIRMED',$confirmedB,'projects','var(--c2)'],
        ['COMPLETED',$completedB,'delivered','var(--c3)'],
      ];
      foreach($cards as [$lbl,$val,$sub,$col]): ?>
      <div class="stat-card" style="--glow:<?=$col?>;">
        <div class="mono" style="font-size:9px;letter-spacing:0.2em;color:rgba(232,213,255,0.3);margin-bottom:10px;"><?=$lbl?></div>
        <div class="orb" style="font-size:2.4rem;font-weight:900;color:<?=$col?>;text-shadow:0 0 20px <?=$col?>40;"><?=$val?></div>
        <div class="mono" style="font-size:10px;color:<?=$col?>60;margin-top:4px;"><?=$sub?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Recent messages + bookings -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <!-- Recent messages -->
      <div class="card" style="padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
          <div class="mono" style="font-size:11px;color:var(--c1);letter-spacing:0.18em;">RECENT MESSAGES</div>
          <button onclick="go('msgs')" class="mono" style="font-size:10px;color:rgba(0,245,255,0.4);background:none;border:none;cursor:pointer;">ALL →</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <?php foreach($recentContacts as $c): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:9px 10px;background:rgba(4,1,10,0.6);border:1px solid rgba(255,45,120,0.07);border-radius:3px;">
            <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,rgba(255,45,120,0.2),rgba(191,95,255,0.2));display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:11px;color:var(--c1);flex-shrink:0;"><?=strtoupper(substr($c['name'],0,1))?></div>
            <div style="flex:1;min-width:0;">
              <div style="display:flex;align-items:center;gap:6px;">
                <span style="font-weight:700;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($c['name'])?></span>
                <?php if($c['status']==='unread'): ?><span class="badge b-unread">NEW</span><?php endif; ?>
              </div>
              <div class="mono" style="font-size:10px;color:rgba(232,213,255,0.35);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($c['subject'])?></div>
            </div>
            <div class="mono" style="font-size:10px;color:rgba(232,213,255,0.2);flex-shrink:0;"><?=date('M d',strtotime($c['created_at']))?></div>
          </div>
          <?php endforeach; ?>
          <?php if(empty($recentContacts)): ?><div class="mono" style="font-size:11px;color:rgba(232,213,255,0.2);text-align:center;padding:20px;">No messages yet</div><?php endif; ?>
        </div>
      </div>
      <!-- Recent bookings -->
      <div class="card" style="padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
          <div class="mono" style="font-size:11px;color:var(--c4);letter-spacing:0.18em;">RECENT BOOKINGS</div>
          <button onclick="go('bookings')" class="mono" style="font-size:10px;color:rgba(0,245,255,0.4);background:none;border:none;cursor:pointer;">ALL →</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <?php foreach($recentBookings as $b): ?>
          <div style="padding:9px 10px;background:rgba(4,1,10,0.6);border:1px solid rgba(255,230,0,0.07);border-radius:3px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
              <span style="font-weight:700;font-size:13px;"><?=htmlspecialchars($b['client_name'])?><?=$b['company']?' <span style="color:rgba(232,213,255,0.35);font-weight:400;font-size:11px;">@ '.htmlspecialchars($b['company']).'</span>':''?></span>
              <span class="badge b-<?=$b['status']?>"><?=strtoupper($b['status'])?></span>
            </div>
            <div class="mono" style="font-size:10px;color:rgba(232,213,255,0.35);"><?=htmlspecialchars($b['project_type'])?><?=$b['budget']?' · '.htmlspecialchars($b['budget']):''?></div>
          </div>
          <?php endforeach; ?>
          <?php if(empty($recentBookings)): ?><div class="mono" style="font-size:11px;color:rgba(232,213,255,0.2);text-align:center;padding:20px;">No bookings yet</div><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Activity log -->
    <div class="card" style="padding:20px;">
      <div class="mono" style="font-size:11px;color:var(--c3);letter-spacing:0.18em;margin-bottom:14px;">// ACTIVITY LOG</div>
      <div style="display:flex;flex-direction:column;gap:0;max-height:220px;overflow-y:auto;">
        <?php foreach($activityLog as $l): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid rgba(191,95,255,0.06);">
          <span class="mono" style="font-size:10px;color:rgba(232,213,255,0.2);flex-shrink:0;"><?=date('H:i',strtotime($l['created_at']))?></span>
          <span class="mono" style="font-size:11px;color:var(--c3);"><?=htmlspecialchars($l['username']??'sys')?></span>
          <span style="color:rgba(232,213,255,0.25);font-size:12px;">→</span>
          <span class="mono" style="font-size:11px;color:rgba(232,213,255,0.5);"><?=htmlspecialchars($l['action'])?></span>
        </div>
        <?php endforeach; ?>
        <?php if(empty($activityLog)): ?><div class="mono" style="font-size:11px;color:rgba(232,213,255,0.2);text-align:center;padding:12px;">No activity logged</div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── MESSAGES ── -->
  <div id="sec-msgs" class="sec">
    <div class="card" style="padding:22px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
        <div class="mono" style="font-size:11px;color:var(--c1);letter-spacing:0.18em;">ALL CONTACT MESSAGES <span style="color:rgba(255,45,120,0.4);">(<?=count($allContacts)?>)</span></div>
        <select onchange="filterTable('msgs-tbody','status',this.value)" style="background:var(--dark);border:1px solid rgba(191,95,255,0.2);color:var(--text);padding:5px 10px;font-family:'Share Tech Mono',monospace;font-size:10px;outline:none;">
          <option value="">ALL</option><option value="unread">UNREAD</option><option value="read">READ</option><option value="replied">REPLIED</option><option value="archived">ARCHIVED</option>
        </select>
      </div>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>NAME</th><th>EMAIL</th><th>SUBJECT</th><th>STATUS</th><th>DATE</th><th>ACTIONS</th></tr></thead>
          <tbody id="msgs-tbody">
            <?php foreach($allContacts as $c): ?>
            <tr data-status="<?=$c['status']?>">
              <td style="font-weight:700;"><?=htmlspecialchars($c['name'])?></td>
              <td class="mono" style="font-size:12px;color:rgba(0,245,255,0.7);"><?=htmlspecialchars($c['email'])?></td>
              <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:rgba(232,213,255,0.7);"><?=htmlspecialchars($c['subject'])?></td>
              <td><span class="badge b-<?=$c['status']?>"><?=strtoupper($c['status'])?></span></td>
              <td class="mono" style="font-size:11px;color:rgba(232,213,255,0.3);"><?=date('d M Y',strtotime($c['created_at']))?></td>
              <td>
                <button onclick='viewMsg(<?=json_encode($c)?>)' class="ab" style="border-color:rgba(0,245,255,0.3);color:rgba(0,245,255,0.7);">VIEW</button>
                <button onclick="upd('contact',<?=$c['id']?>,'read')"    class="ab" style="border-color:rgba(191,95,255,0.3);color:rgba(191,95,255,0.7);margin:0 2px;">READ</button>
                <button onclick="upd('contact',<?=$c['id']?>,'archived')" class="ab" style="border-color:rgba(255,45,120,0.3);color:rgba(255,45,120,0.7);">ARCHIVE</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($allContacts)): ?><tr><td colspan="6" style="text-align:center;padding:28px;color:rgba(232,213,255,0.2);" class="mono">No messages yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── BOOKINGS ── -->
  <div id="sec-bookings" class="sec">
    <div class="card" style="padding:22px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
        <div class="mono" style="font-size:11px;color:var(--c4);letter-spacing:0.18em;">JOB OPPORTUNITIES & BOOKINGS <span style="color:rgba(255,230,0,0.4);">(<?=count($allBookings)?>)</span></div>
        <select onchange="filterBookings(this.value)" style="background:var(--dark);border:1px solid rgba(191,95,255,0.2);color:var(--text);padding:5px 10px;font-family:'Share Tech Mono',monospace;font-size:10px;outline:none;">
          <option value="">ALL STATUS</option><option value="pending">PENDING</option><option value="confirmed">CONFIRMED</option><option value="declined">DECLINED</option><option value="completed">COMPLETED</option>
        </select>
      </div>
      <div id="bookings-list" style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach($allBookings as $b): ?>
        <div class="booking-row" data-status="<?=$b['status']?>" style="border:1px solid rgba(191,95,255,0.1);border-radius:4px;padding:18px;background:rgba(4,1,10,0.5);transition:border-color 0.2s;">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;">
            <div>
              <div style="font-weight:700;font-size:15px;"><?=htmlspecialchars($b['client_name'])?><?=$b['company']?' <span style="color:rgba(232,213,255,0.35);font-size:12px;font-weight:400;">@ '.htmlspecialchars($b['company']).'</span>':''?></div>
              <div class="mono" style="font-size:11px;color:rgba(0,245,255,0.5);margin-top:3px;"><?=htmlspecialchars($b['email'])?><?=$b['phone']?' · '.htmlspecialchars($b['phone']):'';?></div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
              <span class="badge b-<?=$b['status']?>"><?=strtoupper($b['status'])?></span>
              <span class="mono" style="font-size:10px;color:rgba(232,213,255,0.2);"><?=date('d M Y',strtotime($b['created_at']))?></span>
            </div>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:10px;" class="mono">
            <span style="font-size:11px;"><span style="color:rgba(232,213,255,0.3);">TYPE:</span> <?=htmlspecialchars($b['project_type'])?></span>
            <?php if($b['budget']): ?><span style="font-size:11px;"><span style="color:rgba(232,213,255,0.3);">BUDGET:</span> <span style="color:var(--c2);"><?=htmlspecialchars($b['budget'])?></span></span><?php endif; ?>
            <?php if($b['timeline']): ?><span style="font-size:11px;"><span style="color:rgba(232,213,255,0.3);">TIMELINE:</span> <?=htmlspecialchars($b['timeline'])?></span><?php endif; ?>
            <?php if($b['preferred_date']): ?><span style="font-size:11px;"><span style="color:rgba(232,213,255,0.3);">START:</span> <?=date('d M Y',strtotime($b['preferred_date']))?></span><?php endif; ?>
          </div>
          <div style="color:rgba(232,213,255,0.5);font-size:13px;line-height:1.6;margin-bottom:12px;"><?=htmlspecialchars(mb_substr($b['description'],0,220)).(mb_strlen($b['description'])>220?'…':'')?></div>
          <?php if($b['admin_notes']): ?><div style="padding:8px 12px;background:rgba(191,95,255,0.05);border:1px solid rgba(191,95,255,0.12);border-radius:3px;margin-bottom:10px;" class="mono"><span style="font-size:9px;color:rgba(191,95,255,0.4);letter-spacing:0.15em;">NOTE: </span><span style="font-size:11px;color:rgba(232,213,255,0.6);"><?=htmlspecialchars($b['admin_notes'])?></span></div><?php endif; ?>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button onclick="upd('booking',<?=$b['id']?>,'confirmed')"  class="ab" style="border-color:rgba(0,245,255,0.3);color:rgba(0,245,255,0.7);">✓ CONFIRM</button>
            <button onclick="upd('booking',<?=$b['id']?>,'declined')"   class="ab" style="border-color:rgba(255,45,120,0.3);color:rgba(255,45,120,0.7);">✕ DECLINE</button>
            <button onclick="upd('booking',<?=$b['id']?>,'completed')"  class="ab" style="border-color:rgba(191,95,255,0.3);color:rgba(191,95,255,0.7);">◈ COMPLETE</button>
            <button onclick="addNote(<?=$b['id']?>)"                     class="ab" style="border-color:rgba(255,230,0,0.3);color:rgba(255,230,0,0.7);">✎ NOTE</button>
            <button onclick="replyEmail('<?=htmlspecialchars($b['email'])?>')" class="ab" style="border-color:rgba(0,245,255,0.2);color:rgba(0,245,255,0.5);">✉ REPLY</button>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($allBookings)): ?><div class="mono" style="text-align:center;padding:40px;color:rgba(232,213,255,0.2);">No bookings yet</div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── ANALYTICS ── -->
  <div id="sec-analytics" class="sec">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="card" style="padding:20px;"><div class="mono" style="font-size:11px;color:var(--c2);margin-bottom:16px;">INQUIRIES OVER TIME</div><canvas id="ch-line" height="200"></canvas></div>
      <div class="card" style="padding:20px;"><div class="mono" style="font-size:11px;color:var(--c3);margin-bottom:16px;">PROJECT TYPE BREAKDOWN</div><canvas id="ch-donut" height="200"></canvas></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
      <?php
      $conv = $totalBookings>0 ? round($confirmedB/$totalBookings*100).'%' : '0%';
      $mets=[['CONVERSION',$conv,'bookings confirmed'],['PENDING',$pendingB,'awaiting response'],['TOTAL LEADS',$totalContacts+$totalBookings,'all-time inquiries'],['DELIVERED',$completedB,'projects completed']];
      foreach($mets as [$l,$v,$s]): ?>
      <div class="stat-card" style="--glow:var(--c2);text-align:center;padding:18px;">
        <div class="orb" style="font-size:2rem;font-weight:900;color:var(--c2);"><?=$v?></div>
        <div class="mono" style="font-size:9px;letter-spacing:0.18em;color:rgba(0,245,255,0.4);margin-top:4px;"><?=$l?></div>
        <div class="mono" style="font-size:10px;color:rgba(232,213,255,0.25);margin-top:2px;"><?=$s?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── SETTINGS ── -->
  <div id="sec-settings" class="sec">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
      <!-- Portfolio settings -->
      <div class="card" style="padding:24px;">
        <div class="mono" style="font-size:11px;color:var(--c1);letter-spacing:0.18em;margin-bottom:18px;">PORTFOLIO SETTINGS</div>
        <form id="sf" style="display:flex;flex-direction:column;gap:14px;">
          <?php $flds=[['hero_name','YOUR NAME'],['hero_tagline','TAGLINE / ROLE'],['about_text','ABOUT ME TEXT'],['years_experience','YEARS EXPERIENCE'],['projects_completed','PROJECTS COMPLETED'],['clients_worldwide','CLIENTS WORLDWIDE']];
          foreach($flds as [$k,$l]): ?>
          <div>
            <label class="fl"><?=$l?></label>
            <?php if($k==='about_text'): ?>
            <textarea name="<?=$k?>" rows="3" class="fi" style="resize:vertical;"><?=htmlspecialchars($smap[$k]??'')?></textarea>
            <?php else: ?>
            <input type="text" name="<?=$k?>" class="fi" value="<?=htmlspecialchars($smap[$k]??'')?>">
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <button type="button" onclick="saveSets()" class="btn btn-p" style="margin-top:4px;">SAVE SETTINGS</button>
        </form>
      </div>
      <!-- Change password -->
      <div class="card" style="padding:24px;">
        <div class="mono" style="font-size:11px;color:var(--c3);letter-spacing:0.18em;margin-bottom:18px;">CHANGE PASSWORD</div>
        <form id="pwf" style="display:flex;flex-direction:column;gap:14px;">
          <div><label class="fl">CURRENT PASSWORD</label><input type="password" name="current_pw" class="fi"></div>
          <div><label class="fl">NEW PASSWORD</label><input type="password" name="new_pw" class="fi"></div>
          <div><label class="fl">CONFIRM NEW</label><input type="password" name="confirm_pw" class="fi"></div>
          <button type="button" onclick="changePw()" class="btn btn-g" style="margin-top:4px;">UPDATE PASSWORD</button>
        </form>
        <div style="margin-top:32px;">
          <div class="mono" style="font-size:11px;color:rgba(255,45,120,0.4);letter-spacing:0.18em;margin-bottom:14px;">DANGER ZONE</div>
          <div style="padding:14px;background:rgba(255,45,120,0.04);border:1px solid rgba(255,45,120,0.12);border-radius:3px;">
            <div class="mono" style="font-size:11px;color:rgba(232,213,255,0.4);margin-bottom:10px;">Availability status displayed on your portfolio:</div>
            <select id="avail-sel" class="fi" style="width:auto;" onchange="saveAvail(this.value)">
              <option value="open" <?=($smap['availability_status']??'open')==='open'?'selected':''?>>● OPEN TO WORK</option>
              <option value="busy" <?=($smap['availability_status']??'')==='busy'?'selected':''?>>◉ CURRENTLY BUSY</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Message Modal -->
<div id="modal">
  <div id="modal-inner">
    <button onclick="closeModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;color:rgba(255,45,120,0.5);font-size:18px;cursor:pointer;">✕</button>
    <div id="modal-body"></div>
  </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<script>
// ── Navigation ──
function go(name){
  document.querySelectorAll('.sec').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('sec-'+name).classList.add('active');
  document.getElementById('nb-'+name).classList.add('active');
  const titles={dash:'DASHBOARD',msgs:'MESSAGES',bookings:'BOOKINGS',analytics:'ANALYTICS',settings:'SETTINGS'};
  document.getElementById('page-title').textContent=titles[name]||name.toUpperCase();
  if(name==='analytics') initCharts();
}

// ── Toast ──
function toast(msg,ok=true){
  const t=document.getElementById('toast');
  t.textContent=(ok?'✓ ':'⚠ ')+msg;
  t.style.borderColor=ok?'rgba(0,245,255,0.3)':'rgba(255,45,120,0.3)';
  t.style.color=ok?'var(--c2)':'var(--c1)';
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3200);
}

// ── Status update ──
function upd(type,id,status){
  fetch('../api/admin_actions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=update_status&type=${type}&id=${id}&status=${status}`})
  .then(r=>r.json()).then(d=>toast(d.success?`${type} #${id} → ${status}`:'Update failed',d.success));
}

// ── View message modal ──
function viewMsg(d){
  document.getElementById('modal-body').innerHTML=`
    <div class="mono" style="font-size:11px;color:var(--c1);letter-spacing:0.18em;margin-bottom:18px;">// MESSAGE DETAIL</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
      <div><div class="mono" style="font-size:9px;color:rgba(191,95,255,0.45);letter-spacing:0.18em;margin-bottom:4px;">FROM</div><div style="font-weight:700;">${d.name}</div></div>
      <div><div class="mono" style="font-size:9px;color:rgba(191,95,255,0.45);letter-spacing:0.18em;margin-bottom:4px;">EMAIL</div><div class="mono" style="color:var(--c2);font-size:12px;">${d.email}</div></div>
    </div>
    <div style="margin-bottom:14px;"><div class="mono" style="font-size:9px;color:rgba(191,95,255,0.45);letter-spacing:0.18em;margin-bottom:4px;">SUBJECT</div><div>${d.subject}</div></div>
    <div style="margin-bottom:16px;"><div class="mono" style="font-size:9px;color:rgba(191,95,255,0.45);letter-spacing:0.18em;margin-bottom:4px;">MESSAGE</div>
      <div style="padding:14px;background:rgba(4,1,10,0.8);border:1px solid rgba(191,95,255,0.1);border-radius:3px;line-height:1.7;color:rgba(232,213,255,0.7);font-size:13px;">${d.message}</div>
    </div>
    <div class="mono" style="font-size:10px;color:rgba(232,213,255,0.25);margin-bottom:18px;">Received: ${d.created_at}</div>
    <div style="display:flex;gap:10px;">
      <a href="mailto:${d.email}?subject=Re: ${d.subject}" class="btn btn-p" style="flex:1;text-align:center;text-decoration:none;display:block;padding:10px;">REPLY BY EMAIL</a>
      <button onclick="upd('contact',${d.id},'archived');closeModal();" class="btn btn-g" style="flex:1;">ARCHIVE</button>
    </div>`;
  document.getElementById('modal').classList.add('open');
  upd('contact',d.id,'read');
}
function closeModal(){document.getElementById('modal').classList.remove('open');}
document.getElementById('modal').addEventListener('click',function(e){if(e.target===this)closeModal();});

// ── Filter table rows ──
function filterTable(tbodyId,attr,val){
  document.querySelectorAll('#'+tbodyId+' tr').forEach(r=>{
    r.style.display=(!val||r.dataset[attr]===val)?'':'none';
  });
}
function filterBookings(val){
  document.querySelectorAll('.booking-row').forEach(r=>{
    r.style.display=(!val||r.dataset.status===val)?'':'none';
  });
}

// ── Add note ──
function addNote(id){
  const note=prompt('Admin note for booking #'+id+':');
  if(note!==null&&note.trim()){
    fetch('../api/admin_actions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=add_note&id=${id}&note=${encodeURIComponent(note)}`})
    .then(r=>r.json()).then(d=>toast(d.success?'Note saved':'Failed',d.success));
  }
}

function replyEmail(email){window.open('mailto:'+email,'_blank');}

// ── Save settings ──
function saveSets(){
  const fd=new FormData(document.getElementById('sf'));
  fd.append('action','save_settings');
  fetch('../api/admin_actions.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>toast(d.message||'Saved',d.success));
}

function changePw(){
  const fd=new FormData(document.getElementById('pwf'));
  fd.append('action','change_password');
  fetch('../api/admin_actions.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>toast(d.message,d.success));
}

function saveAvail(val){
  fetch('../api/admin_actions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=save_settings&availability_status=${val}`})
  .then(r=>r.json()).then(d=>toast(d.success?'Availability updated':'Failed',d.success));
}

// ── Charts ──
let chartsInit=false;
function initCharts(){
  if(chartsInit)return; chartsInit=true;
  const grd={color:'rgba(191,95,255,0.07)'};
  const tick={color:'rgba(232,213,255,0.25)',font:{family:'Share Tech Mono',size:10}};

  new Chart(document.getElementById('ch-line'),{
    type:'line',
    data:{
      labels:['Jan','Feb','Mar','Apr','May','Jun'],
      datasets:[
        {label:'Messages',data:[2,5,3,8,4,10],borderColor:'#ff2d78',backgroundColor:'rgba(255,45,120,0.08)',tension:0.4,fill:true,pointBackgroundColor:'#ff2d78'},
        {label:'Bookings',data:[1,2,4,3,7,6],borderColor:'#00f5ff',backgroundColor:'rgba(0,245,255,0.06)',tension:0.4,fill:true,pointBackgroundColor:'#00f5ff'},
      ]
    },
    options:{responsive:true,plugins:{legend:{labels:{color:'rgba(232,213,255,0.5)',font:{family:'Share Tech Mono',size:10}}}},scales:{x:{ticks:tick,grid:grd},y:{ticks:tick,grid:grd}}}
  });

  new Chart(document.getElementById('ch-donut'),{
    type:'doughnut',
    data:{
      labels:['Web Dev','Mobile','Full Stack','Consulting','Other'],
      datasets:[{data:[35,20,28,10,7],backgroundColor:['#ff2d78','#00f5ff','#bf5fff','#ffe600','#ff6b35'],borderWidth:0,hoverOffset:6}]
    },
    options:{responsive:true,plugins:{legend:{labels:{color:'rgba(232,213,255,0.5)',font:{family:'Share Tech Mono',size:10}}}},cutout:'65%'}
  });
}
</script>
</body>
</html>
