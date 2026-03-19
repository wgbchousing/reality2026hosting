<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// ── Brevo config ───────────────────────────────────────────────────────────
define('BREVO_API_KEY',  'YOUR_BREVO_API_KEY_HERE'); // Get from Brevo → SMTP & API → API Keys
define('FROM_EMAIL',     'wgbc.housing@gmail.com');
define('FROM_NAME',      'Word of Grace Bible Church');

// Template IDs
define('TMPL_GUEST',    1); // Guest Confirmation
define('TMPL_HOST',     2); // Host Notification
define('TMPL_APPROVAL', 3); // Host Approval

// ── Get POST data ──────────────────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['success'=>false,'error'=>'No data received']); exit; }

$type       = $data['type']        ?? '';
$guestName  = $data['guest_name']  ?? '';
$guestEmail = $data['guest_email'] ?? '';
$guestPhone = $data['guest_phone'] ?? '';
$hostName   = $data['host_name']   ?? '';
$hostEmail  = $data['host_email']  ?? '';
$hostPhone  = $data['host_phone']  ?? '';
$party      = $data['party']       ?? '';
$members    = $data['members']     ?? '—';
$notes      = $data['notes']       ?? '—';
$spotsLeft  = $data['spots_left']  ?? '';
$capacity   = $data['capacity']    ?? '';
$pref       = $data['pref']        ?? '';

// ── Build request based on type ────────────────────────────────────────────
if ($type === 'guest_confirmation') {

  $to         = $guestEmail;
  $toName     = $guestName;
  $templateId = TMPL_GUEST;
  $params     = [
    'guest_name'  => $guestName,
    'guest_email' => $guestEmail,
    'guest_phone' => $guestPhone,
    'host_name'   => $hostName,
    'host_email'  => $hostEmail,
    'host_phone'  => $hostPhone,
    'party'       => $party,
    'members'     => $members,
    'notes'       => $notes,
  ];

} elseif ($type === 'host_notification') {

  $to         = $hostEmail;
  $toName     = $hostName;
  $templateId = TMPL_HOST;
  $params     = [
    'host_name'   => $hostName,
    'host_email'  => $hostEmail,
    'host_phone'  => $hostPhone,
    'guest_name'  => $guestName,
    'guest_email' => $guestEmail,
    'guest_phone' => $guestPhone,
    'party'       => $party,
    'members'     => $members,
    'notes'       => $notes,
    'spots_left'  => $spotsLeft,
  ];

} elseif ($type === 'host_approval') {

  $to         = $hostEmail;
  $toName     = $hostName;
  $templateId = TMPL_APPROVAL;
  $params     = [
    'host_name' => $hostName,
    'capacity'  => $capacity,
    'pref'      => $pref,
    'notes'     => $notes,
  ];

} else {
  echo json_encode(['success'=>false,'error'=>'Unknown email type: '.$type]);
  exit;
}

// ── Send via Brevo API ─────────────────────────────────────────────────────
$payload = json_encode([
  'to'         => [['email' => $to, 'name' => $toName]],
  'templateId' => $templateId,
  'params'     => $params,
  'sender'     => ['email' => FROM_EMAIL, 'name' => FROM_NAME],
]);

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => $payload,
  CURLOPT_HTTPHEADER     => [
    'Content-Type: application/json',
    'api-key: ' . BREVO_API_KEY,
  ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 201) {
  echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
  echo json_encode(['success' => false, 'error' => 'Brevo error: ' . $response]);
}
?>
