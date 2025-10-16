<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function respond($data, $code=200){ http_response_code($code); $out = ob_get_clean(); if ($out && trim($out)!==''){ $data['debug']=$out; } echo json_encode($data); exit; }

try{
  if (!isset($_SESSION['user']['id'])) respond(['success'=>false,'message'=>'Not authenticated'],401);
  $raw = file_get_contents('php://input');
  $input = json_decode($raw,true);
  $id = isset($input['id']) ? (int)$input['id'] : 0;
  if ($id<=0) respond(['success'=>false,'message'=>'Invalid id'],422);

  $db = Database::getInstance(); $uid=(int)$_SESSION['user']['id'];
  $bill = $db->fetchOne('SELECT id, name, amount, due_date, COALESCE(is_paid, FALSE) AS is_paid FROM bills WHERE id = :id AND user_id = :u', ['id'=>$id,'u'=>$uid]);
  if (!$bill) respond(['success'=>false,'message'=>'Not found'],404);
  if (!empty($bill['is_paid'])) respond(['success'=>false,'message'=>'Bill already paid'],400);

  $user = $db->fetchOne('SELECT email, name FROM users WHERE id = :u', ['u'=>$uid]);
  if (!$user || empty($user['email'])) respond(['success'=>false,'message'=>'User email not found'],400);

  $smtp = include __DIR__ . '/smtp_config.php';
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = $smtp['smtp_host'];
  $mail->SMTPAuth = true;
  $mail->Username = $smtp['smtp_username'];
  $mail->Password = $smtp['smtp_password'];
  $mail->SMTPSecure = $smtp['smtp_secure'];
  $mail->Port = $smtp['smtp_port'];
  $mail->SMTPOptions = [ 'ssl'=>[ 'verify_peer'=>false, 'verify_peer_name'=>false, 'allow_self_signed'=>true ] ];
  if (isset($smtp['smtp_timeout'])) $mail->Timeout = (int)$smtp['smtp_timeout'];

  $mail->setFrom($smtp['smtp_from_email'], $smtp['smtp_from_name']);
  $mail->addAddress($user['email'], $user['name'] ?? '');
  $mail->isHTML(true);

  $due = date('M d, Y', strtotime($bill['due_date']));
  $amt = number_format((float)$bill['amount'], 2);
  $subject = 'Bill Reminder: ' . $bill['name'] . ' due ' . $due;
  $body = '<div style="font-family:sans-serif;">
    <h2 style="color:#0ea5e9; margin:0 0 8px;">Payment Reminder</h2>
    <p>Hi ' . htmlspecialchars($user['name'] ?? 'there') . ',</p>
    <p>This is a friendly reminder for your upcoming bill:</p>
    <ul>
      <li><strong>Bill:</strong> ' . htmlspecialchars($bill['name']) . '</li>
      <li><strong>Amount:</strong> ' . $amt . '</li>
      <li><strong>Due Date:</strong> ' . $due . '</li>
    </ul>
    <p>Please make sure to pay it on time to avoid any late fees.</p>
    <p>Thanks,<br/>FinCtrl</p>
  </div>';

  $mail->Subject = $subject;
  $mail->Body = $body;
  $mail->AltBody = "Bill Reminder: {$bill['name']} amount {$amt} due {$due}";

  $ok = $mail->send();
  if ($ok) respond(['success'=>true, 'message'=>'Email sent']);
  respond(['success'=>false,'message'=>'Failed to send email'],500);
}catch(Throwable $e){ respond(['success'=>false,'message'=>$e->getMessage()],500); }
