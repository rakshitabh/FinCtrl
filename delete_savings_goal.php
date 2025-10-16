<?php
// UTF-8 without BOM
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/database.php';

function respond(array $d, int $code=200){ http_response_code($code); $o=ob_get_clean(); if($o&&trim($o)!=='') $d['debug']=$o; echo json_encode($d); exit; }

try{
  if (!isset($_SESSION['user']['id'])) respond(['success'=>false,'message'=>'Not authenticated'],401);
  // Accept JSON body or form-encoded id
  $goalId = null;
  if (isset($_POST['id'])) { $goalId = (int)$_POST['id']; }
  else {
    $raw = file_get_contents('php://input');
    if ($raw) { $input = json_decode($raw,true); if (is_array($input)) $goalId = (int)($input['id'] ?? 0); }
  }
  if (!$goalId || $goalId <= 0) respond(['success'=>false,'message'=>'Goal id required'],422);

  $db = Database::getInstance(); $uid = (int)$_SESSION['user']['id'];
  $goal = $db->fetchOne('SELECT id FROM savings_goals WHERE id = :id AND user_id = :u', ['id'=>$goalId,'u'=>$uid]);
  if (!$goal) respond(['success'=>false,'message'=>'Goal not found'],404);

  $db->delete('savings_goals','id = :id AND user_id = :u', ['id'=>$goalId,'u'=>$uid]);
  respond(['success'=>true,'message'=>'Goal deleted']);
} catch (Throwable $e){ respond(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()],500); }
?>
