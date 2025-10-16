<?php
// UTF-8 without BOM
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/database.php';

function respond(array $d, int $code=200){ http_response_code($code); $o=ob_get_clean(); if($o&&trim($o)!=='') $d['debug']=$o; echo json_encode($d); exit; }

try{
  if (!isset($_SESSION['user']['id'])) respond(['success'=>false,'message'=>'Not authenticated'],401);
  $db = Database::getInstance();
  $uid = (int)$_SESSION['user']['id'];
  $rows = $db->fetchAll("SELECT id, name, target_amount, current_amount, start_date, target_date, icon, color, completed FROM savings_goals WHERE user_id = :u ORDER BY id DESC", ['u'=>$uid]);
  // Deduplicate goals by (name,target,start,targetDate) preferring non-completed and most recent
  $byKey = [];
  foreach ($rows as $r) {
    $key = strtolower(trim($r['name'])) . '|' . (string)$r['target_amount'] . '|' . (string)$r['start_date'] . '|' . (string)($r['target_date'] ?? '');
    if (!isset($byKey[$key])) { $byKey[$key] = $r; continue; }
    $cur = $byKey[$key];
    $candidateBetter = (!$r['completed'] && $cur['completed']) || ($r['completed'] === $cur['completed'] && (int)$r['id'] > (int)$cur['id']);
    if ($candidateBetter) { $byKey[$key] = $r; }
  }
  // Build output goals list
  $goals = [];
  foreach ($byKey as $r) {
    $t = (float)$r['target_amount'];
    $c = (float)$r['current_amount'];
    $pct = $t>0 ? min(100, round(($c/$t)*100)) : 0;
    $goals[] = [
      'id' => (int)$r['id'],
      'name' => $r['name'],
      'target' => $t,
      'current' => $c,
      'percent' => $pct,
      'targetFormatted' => number_format($t,2),
      'currentFormatted' => number_format($c,2),
      'startDate' => $r['start_date'],
      'targetDate' => $r['target_date'],
      'icon' => $r['icon'],
      'color' => $r['color'],
      'completed' => !!$r['completed']
    ];
  }
  // Aggregate from deduped active goals
  $cur = 0.0; $tgt = 0.0;
  foreach ($goals as $g) { if (!$g['completed']) { $cur += $g['current']; $tgt += $g['target']; } }
  $percent = $tgt>0 ? min(100, round(($cur/$tgt)*100)) : 0;
  respond(['success'=>true,'goals'=>$goals,'aggregate'=>[
    'current'=>$cur,'target'=>$tgt,'percent'=>$percent,
    'currentFormatted'=>number_format($cur,2),'targetFormatted'=>number_format($tgt,2)
  ]]);
} catch (Throwable $e){ respond(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()],500); }
?>
