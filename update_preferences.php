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
  $prefs = [
    'theme' => in_array(($data['theme'] ?? 'system'), ['system','light','dark']) ? $data['theme'] : 'system',
    'budget25' => !!($data['budget25'] ?? false),
    'budget50' => !!($data['budget50'] ?? false),
    'budget100' => !!($data['budget100'] ?? false),
    'billReminders' => !!($data['billReminders'] ?? false),
    'appUpdates' => !!($data['appUpdates'] ?? false),
    'leadDays' => max(1, min(7, (int)($data['leadDays'] ?? 2)))
  ];
  $row = $db->fetchOne('SELECT id FROM user_profiles WHERE user_id = :id', ['id'=>$uid]);
  if ($row){
    $db->update('user_profiles', ['notification_preferences'=>json_encode($prefs), 'updated_at'=>date('Y-m-d H:i:s')], 'user_id = :id', ['id'=>$uid]);
  } else {
    $db->insert('user_profiles', ['user_id'=>$uid, 'notification_preferences'=>json_encode($prefs), 'updated_at'=>date('Y-m-d H:i:s')]);
  }
  output_json(['success'=>true]);
}catch(Throwable $e){ output_json(['success'=>false,'message'=>$e->getMessage()]); }
?>
