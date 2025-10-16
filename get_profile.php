<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/database.php';

function output_json($data){
  ob_clean();
  echo json_encode($data);
  ob_end_flush();
  exit;
}

if (!isset($_SESSION['user']['id'])) {
  output_json(['success'=>false,'message'=>'Not authenticated']);
}

try{
  $db = Database::getInstance();
  $userId = (int)$_SESSION['user']['id'];
  $u = $db->fetchOne('SELECT u.id, u.name, u.email, p.avatar_url FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id LIMIT 1', ['id'=>$userId]);
  if (!$u) {
    // Fallback to session
    $sess = $_SESSION['user'];
    output_json(['success'=>true,'id'=>$userId,'name'=>$sess['name'] ?? '', 'email'=>$sess['email'] ?? '', 'avatarUrl'=>null]);
  }
  $avatarUrl = !empty($u['avatar_url']) ? $u['avatar_url'] : null;
  output_json(['success'=>true,'id'=>$u['id'],'name'=>$u['name'] ?? '', 'email'=>$u['email'] ?? '', 'avatarUrl'=>$avatarUrl]);
} catch (Throwable $e){
  output_json(['success'=>false,'message'=>$e->getMessage()]);
}
?>