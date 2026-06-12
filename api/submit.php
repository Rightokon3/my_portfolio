<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../includes/config.php';

$db     = Database::get();
$action = $_GET['action'] ?? '';

// ── Contact message ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'contact') {
    $name    = clean($_POST['name']    ?? '');
    $email   = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = clean($_POST['subject'] ?? '');
    $message = clean($_POST['message'] ?? '');

    if (!$name || !$email || !$subject || !$message)
        json(['success'=>false, 'message'=>'All fields are required.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        json(['success'=>false, 'message'=>'Invalid email address.'], 400);

    $id = $db->insert(
        "INSERT INTO contacts (name,email,subject,message) VALUES (?,?,?,?)",
        [$name, $email, $subject, $message]
    );
    json(['success'=>true, 'message'=>"Message received! I'll reply within 24 hours.", 'id'=>$id]);
}

// ── Booking / job opportunity ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'booking') {
    $cn   = clean($_POST['client_name']    ?? '');
    $co   = clean($_POST['company']        ?? '');
    $em   = filter_var($_POST['email']     ?? '', FILTER_SANITIZE_EMAIL);
    $ph   = clean($_POST['phone']          ?? '');
    $pt   = clean($_POST['project_type']   ?? '');
    $bu   = clean($_POST['budget']         ?? '');
    $tl   = clean($_POST['timeline']       ?? '');
    $de   = clean($_POST['description']    ?? '');
    $pd   = $_POST['preferred_date']       ?? null;

    if (!$cn || !$em || !$pt || !$de)
        json(['success'=>false, 'message'=>'Please fill in all required fields.'], 400);
    if (!filter_var($em, FILTER_VALIDATE_EMAIL))
        json(['success'=>false, 'message'=>'Invalid email address.'], 400);

    $id = $db->insert(
        "INSERT INTO bookings (client_name,company,email,phone,project_type,budget,timeline,description,preferred_date) VALUES (?,?,?,?,?,?,?,?,?)",
        [$cn, $co, $em, $ph, $pt, $bu, $tl, $de, $pd ?: null]
    );
    json(['success'=>true, 'message'=>'Booking submitted! I\'ll confirm within 24 hours.', 'id'=>$id]);
}

json(['error'=>'Invalid request'], 400);
?>
