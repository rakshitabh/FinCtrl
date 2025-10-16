<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/database.php';
function output_json($d){ ob_clean(); echo json_encode($d); ob_end_flush(); exit; }
if (!isset($_SESSION['user']['id'])) output_json(['success'=>false,'message'=>'Not authenticated']);

try{
  $db = Database::getInstance(); $uid=(int)$_SESSION['user']['id'];
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $email = isset($data['email']) ? trim($data['email']) : '';
  $code = isset($data['code']) ? trim($data['code']) : '';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $code === '') output_json(['success'=>false,'message'=>'Invalid request']);

  // Verify OTP
  // Find OTP by email+code
  $codeRow = $db->fetchOne('SELECT id, expires_at, verified FROM otp_verifications WHERE email=:email AND otp=:otp ORDER BY created_at DESC LIMIT 1', ['email'=>$email,'otp'=>$code]);
  if (!$codeRow) output_json(['success'=>false,'message'=>'Invalid code']);
  if (!empty($codeRow['verified'])) output_json(['success'=>false,'message'=>'Code already used']);
  if (strtotime($codeRow['expires_at']) < time()) output_json(['success'=>false,'message'=>'Code expired']);

  // Update email
  $exists = $db->fetchOne('SELECT id FROM users WHERE email = :email AND id <> :id', ['email'=>$email, 'id'=>$uid]);
  if ($exists) output_json(['success'=>false,'message'=>'Email already in use']);
  $db->update('users', ['email'=>$email, 'email_verified'=>true, 'updated_at'=>date('Y-m-d H:i:s')], 'id = :id', ['id'=>$uid]);
  $_SESSION['user']['email'] = $email;

  // Mark OTP verified
  $db->update('otp_verifications', ['verified'=>true], 'id = :id', ['id'=>$codeRow['id']]);

  output_json(['success'=>true,'message'=>'Email updated']);
}catch(Throwable $e){ output_json(['success'=>false,'message'=>$e->getMessage()]); }
?>
