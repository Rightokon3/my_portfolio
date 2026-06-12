<?php
session_start();
require_once '../includes/config.php';
requireAuth();
header('Content-Type: application/json');

$db  = Database::get();
$act = $_POST['action'] ?? '';

switch ($act) {

    case 'update_status':
        $type   = clean($_POST['type']   ?? '');
        $id     = (int)($_POST['id']     ?? 0);
        $status = clean($_POST['status'] ?? '');
        if ($type === 'contact') {
            if (!in_array($status, ['unread','read','replied','archived'])) json(['success'=>false]);
            $db->query("UPDATE contacts SET status=? WHERE id=?", [$status,$id]);
            logAction("Contact #$id → $status");
            json(['success'=>true]);
        }
        if ($type === 'booking') {
            if (!in_array($status, ['pending','confirmed','declined','completed'])) json(['success'=>false]);
            $db->query("UPDATE bookings SET status=? WHERE id=?", [$status,$id]);
            logAction("Booking #$id → $status");
            json(['success'=>true]);
        }
        json(['success'=>false]);

    case 'add_note':
        $id   = (int)($_POST['id']   ?? 0);
        $note = clean($_POST['note'] ?? '');
        $db->query("UPDATE bookings SET admin_notes=? WHERE id=?", [$note,$id]);
        logAction("Note added to booking #$id");
        json(['success'=>true]);

    case 'save_settings':
        $keys = ['hero_name','hero_tagline','about_text','years_experience','projects_completed','clients_worldwide'];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $v = clean($_POST[$k]);
                $db->query("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?", [$k,$v,$v]);
            }
        }
        logAction('Settings updated');
        json(['success'=>true, 'message'=>'Settings saved!']);

    case 'change_password':
        $cur  = $_POST['current_pw'] ?? '';
        $new  = $_POST['new_pw']     ?? '';
        $conf = $_POST['confirm_pw'] ?? '';
        if ($new !== $conf)       json(['success'=>false,'message'=>'Passwords do not match.']);
        if (strlen($new) < 8)     json(['success'=>false,'message'=>'Minimum 8 characters.']);
        $user = $db->one("SELECT * FROM admin_users WHERE id=?", [$_SESSION['admin_id']]);
        if (!$user || !password_verify($cur, $user['password_hash']))
            json(['success'=>false,'message'=>'Current password is wrong.']);
        $db->query("UPDATE admin_users SET password_hash=? WHERE id=?",
            [password_hash($new, PASSWORD_BCRYPT), $_SESSION['admin_id']]);
        logAction('Password changed');
        json(['success'=>true,'message'=>'Password updated!']);

    default:
        json(['success'=>false,'message'=>'Unknown action'], 400);
}
?>
