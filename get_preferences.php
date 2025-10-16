<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/database.php';
function output_json($d){ ob_clean(); echo json_encode($d); ob_end_flush(); exit; }
if (!isset($_SESSION['user']['id'])) output_json(['success'=>false,'message'=>'Not authenticated']);
try{
  $db = Database::getInstance(); $uid=(int)$_SESSION['user']['id'];
  $row = $db->fetchOne('SELECT notification_preferences FROM user_profiles WHERE user_id = :id', ['id'=>$uid]);
  $prefs = ['theme'=>'system','budget25'=>false,'budget50'=>false,'budget100'=>false,'billReminders'=>false,'appUpdates'=>false,'leadDays'=>2];
  if ($row && !empty($row['notification_preferences'])){
    $obj = json_decode($row['notification_preferences'], true);
    if (is_array($obj)) $prefs = array_merge($prefs, $obj);
  }
  output_json(['success'=>true,'prefs'=>$prefs]);
}catch(Throwable $e){ output_json(['success'=>false,'message'=>$e->getMessage()]); }
?>
