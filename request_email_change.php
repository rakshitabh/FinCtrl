<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/vendor/autoload.php';
// Load SMTP config as array
$smtp = include __DIR__ . '/smtp_config.php';
function output_json($d){ ob_clean(); echo json_encode($d); ob_end_flush(); exit; }
if (!isset($_SESSION['user']['id'])) output_json(['success'=>false,'message'=>'Not authenticated']);

try{
  $db = Database::getInstance(); $uid=(int)$_SESSION['user']['id'];
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $newEmail = isset($data['email']) ? trim($data['email']) : '';
  if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) output_json(['success'=>false,'message'=>'Invalid email']);
  // Check not used
  $exists = $db->fetchOne('SELECT id FROM users WHERE email = :email AND id <> :id', ['email'=>$newEmail,'id'=>$uid]);
  if ($exists) output_json(['success'=>false,'message'=>'Email already in use']);

  // Generate OTP, store in otp_verifications
  $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $expires = date('Y-m-d H:i:s', time()+10*60);
  $db->insert('otp_verifications', [
    'email' => $newEmail,
    'otp' => $otp,
    'expires_at' => $expires,
    'verified' => false
  ]);

  // Send OTP using PHPMailer
  $mail = new PHPMailer\PHPMailer\PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = $smtp['smtp_host'];
  $mail->SMTPAuth = true;
  $mail->Username = $smtp['smtp_username'];
  $mail->Password = $smtp['smtp_password'];
  $mail->SMTPSecure = $smtp['smtp_secure'];
  $mail->Port = $smtp['smtp_port'];
  $mail->Timeout = isset($smtp['smtp_timeout']) ? (int)$smtp['smtp_timeout'] : 30;
  if (!empty($smtp['smtp_debug'])) $mail->SMTPDebug = (int)$smtp['smtp_debug'];
  $mail->setFrom($smtp['smtp_from_email'], $smtp['smtp_from_name']);
  $mail->addAddress($newEmail);
  $mail->isHTML(true);
  $mail->Subject = 'Verify your new email';
  $mail->Body = '<p>Your verification code is <strong>'.$otp.'</strong>. It expires in 10 minutes.</p>';
  $mail->send();

  output_json(['success'=>true,'message'=>'OTP sent to new email']);
}catch(Throwable $e){ output_json(['success'=>false,'message'=>$e->getMessage()]); }
?>
