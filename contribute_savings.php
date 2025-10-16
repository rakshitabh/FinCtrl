<?php
// UTF-8 without BOM
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/database.php';

function respond(array $d, int $code=200){ http_response_code($code); $o=ob_get_clean(); if($o&&trim($o)!=='') $d['debug']=$o; echo json_encode($d); exit; }

try{
  if (!isset($_SESSION['user']['id'])) respond(['success'=>false,'message'=>'Not authenticated'],401);
  $raw = file_get_contents('php://input');
  $input = json_decode($raw,true);
  if (!is_array($input)) respond(['success'=>false,'message'=>'Invalid JSON body'],400);
  $goalId = isset($input['goalId']) ? (int)$input['goalId'] : 0;
  $amount = isset($input['amount']) ? (float)$input['amount'] : 0.0;
  if ($goalId<=0 || $amount<=0) respond(['success'=>false,'message'=>'Goal and positive amount are required'],422);
  $db = Database::getInstance(); $uid = (int)$_SESSION['user']['id'];
  $goal = $db->fetchOne('SELECT id, current_amount, target_amount, completed FROM savings_goals WHERE id = :id AND user_id = :u', ['id'=>$goalId,'u'=>$uid]);
  if (!$goal) respond(['success'=>false,'message'=>'Goal not found'],404);
  $target = (float)$goal['target_amount'];
  $newCur = (float)$goal['current_amount'] + $amount;
  if ($newCur > $target) $newCur = $target; // cap at target
  $completed = $goal['completed'] ? true : ($newCur >= $target);
  $db->update('savings_goals', ['current_amount'=>$newCur, 'completed'=>$completed], 'id = :id AND user_id = :u', ['id'=>$goalId,'u'=>$uid]);
  respond(['success'=>true,'message'=>'Contribution added','current'=>number_format($newCur,2),'completed'=>$completed]);
} catch (Throwable $e){ respond(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()],500); }
?>
