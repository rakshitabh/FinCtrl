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
  $name = trim((string)($input['name'] ?? ''));
  $target = isset($input['target']) ? (float)$input['target'] : 0.0;
  $start = isset($input['startDate']) && $input['startDate'] !== '' ? (string)$input['startDate'] : date('Y-m-d');
  $targetDate = isset($input['targetDate']) && $input['targetDate'] !== '' ? (string)$input['targetDate'] : null;
  if ($name === '' || $target <= 0) respond(['success'=>false,'message'=>'Name and positive target are required'],422);
  if (mb_strlen($name) > 100) respond(['success'=>false,'message'=>'Name must be 100 characters or fewer'],422);
  try { $sd = new DateTime($start); $start = $sd->format('Y-m-d'); } catch(Throwable $e){ respond(['success'=>false,'message'=>'Invalid start date'],422);} 
  if ($targetDate) { 
    try { $td = new DateTime($targetDate); $targetDate = $td->format('Y-m-d'); } catch(Throwable $e){ respond(['success'=>false,'message'=>'Invalid target date'],422);} 
    // Enforce target date after start date
    if (strtotime($targetDate) <= strtotime($start)) {
      respond(['success'=>false,'message'=>'End date must be after start date'],422);
    }
    // Enforce target date after today (strictly in the future)
    $today = date('Y-m-d');
    if (strtotime($targetDate) <= strtotime($today)) {
      respond(['success'=>false,'message'=>'End date must be after today'],422);
    }
  }
  $db = Database::getInstance(); $uid = (int)$_SESSION['user']['id'];
  // Prevent duplicates: same user, name (case-insensitive), target, start and target date
  $existing = $db->fetchOne(
    "SELECT id FROM savings_goals 
      WHERE user_id = :u 
        AND LOWER(name) = LOWER(:n) 
        AND target_amount = :t 
        AND start_date = :s 
        AND target_date IS NOT DISTINCT FROM CAST(:td AS DATE) 
        AND completed = FALSE 
      LIMIT 1",
    ['u'=>$uid,'n'=>$name,'t'=>$target,'s'=>$start,'td'=>$targetDate]
  );
  if ($existing) {
    respond(['success'=>true,'id'=>(int)$existing['id'],'message'=>'Goal already exists']);
  }
  $id = (int)$db->insert('savings_goals', [
    'user_id'=>$uid,
    'name'=>$name,
    'target_amount'=>$target,
    'current_amount'=>0.0,
    'start_date'=>$start,
    'target_date'=>$targetDate,
    'completed'=>false
  ]);
  respond(['success'=>true,'id'=>$id,'message'=>'Savings goal created']);
} catch (Throwable $e){ respond(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()],500); }
?>
