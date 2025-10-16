<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/database.php';

function output_json($d){ ob_clean(); echo json_encode($d); ob_end_flush(); exit; }
if (!isset($_SESSION['user']['id'])) output_json(['success'=>false,'message'=>'Not authenticated']);

try{
  $db = Database::getInstance();
  $userId = (int)$_SESSION['user']['id'];
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $current = $data['current'] ?? '';
  $password = $data['password'] ?? '';
  // Strong password policy: >=8 chars, upper, lower, digit, special
  $hasLen = strlen($password) >= 8;
  $hasUpper = preg_match('/[A-Z]/', $password);
  $hasLower = preg_match('/[a-z]/', $password);
  $hasDigit = preg_match('/\d/', $password);
  $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
  if (!($hasLen && $hasUpper && $hasLower && $hasDigit && $hasSpecial)){
    output_json(['success'=>false,'message'=>'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.']);
  }

  $u = $db->fetchOne('SELECT password FROM users WHERE id = :id', ['id'=>$userId]);
  if (!$u) output_json(['success'=>false,'message'=>'User not found']);
  if (!password_verify($current, $u['password'])) output_json(['success'=>false,'message'=>'Current password incorrect']);
  // New password must be different from current
  if (password_verify($password, $u['password'])) {
    output_json(['success'=>false,'message'=>'New password must be different from the current password']);
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $db->update('users', ['password'=>$hash, 'updated_at'=>date('Y-m-d H:i:s')], 'id = :id', ['id'=>$userId]);
  output_json(['success'=>true,'message'=>'Password updated']);
} catch (Throwable $e){
  output_json(['success'=>false,'message'=>$e->getMessage()]);
}
?>