<?php
ob_start();
session_start();
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');

function respond($data){
  ob_clean(); echo json_encode($data); ob_end_flush(); exit;
}

if (!isset($_SESSION['user']['id'])) {
  respond(['success'=>false,'needs'=>false,'message'=>'Not authenticated']);
}

try{
  $db = Database::getInstance();
  $uid = (int)$_SESSION['user']['id'];

  $profile = $db->fetchOne("SELECT currency, notification_preferences FROM user_profiles WHERE user_id = :id", ['id'=>$uid]);
  $needsProfile = false;
  if (!$profile) { $needsProfile = true; }
  else {
    $prefs = [];
    if (!empty($profile['notification_preferences'])) {
      $decoded = json_decode($profile['notification_preferences'], true);
      if (is_array($decoded)) $prefs = $decoded;
    }
    if (empty($profile['currency']) || !array_key_exists('email', $prefs)) { $needsProfile = true; }
  }

  $start = date('Y-m-01');
  $end = date('Y-m-t');
  $budget = $db->fetchOne(
    "SELECT id FROM budgets WHERE user_id = :id AND period='monthly' AND start_date >= :start AND start_date <= :end LIMIT 1",
    ['id'=>$uid, 'start'=>$start, 'end'=>$end]
  );
  $needsBudget = !$budget;

  respond(['success'=>true,'needs'=> ($needsProfile || $needsBudget) ]);
} catch (Throwable $e){
  respond(['success'=>false,'needs'=>false,'message'=>$e->getMessage()]);
}
