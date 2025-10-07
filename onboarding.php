<?php
ob_start();
session_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');

function output_json($ok, $msg = '', $extra = []){
  ob_clean();
  echo json_encode(array_merge(['success'=>$ok, 'message'=>$msg], $extra));
  ob_end_flush();
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') output_json(false, 'Invalid request method');
if (!isset($_SESSION['user']['id'])) output_json(false, 'Not authenticated');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$monthly = isset($input['monthlyBudget']) ? floatval($input['monthlyBudget']) : 0;
$currency = isset($input['currency']) ? trim($input['currency']) : null;
$emailAlerts = isset($input['emailAlerts']) ? (bool)$input['emailAlerts'] : false;

if ($monthly <= 0) output_json(false, 'Monthly budget must be greater than 0');

try{
  $db = Database::getInstance();
  $userId = (int)$_SESSION['user']['id'];
  $db->beginTransaction();

  // Upsert user profile
  $profile = $db->fetchOne('SELECT id FROM user_profiles WHERE user_id = :id', ['id'=>$userId]);
  $prefs = json_encode(['email'=>$emailAlerts, 'push'=>false]);
  $now = date('Y-m-d H:i:s');

  if ($profile) {
    $db->update('user_profiles', [
      'currency' => $currency ?: 'USD',
      'notification_preferences' => $prefs,
      'updated_at' => $now
    ], 'user_id = :user_id', ['user_id' => $userId]);
  } else {
    $db->insert('user_profiles', [
      'user_id' => $userId,
      'currency' => $currency ?: 'USD',
      'notification_preferences' => $prefs,
      'updated_at' => $now
    ]);
  }

  // Create monthly budget for current month if missing
  $start = date('Y-m-01');
  $end = date('Y-m-t');
  $existingBudget = $db->fetchOne(
    "SELECT id FROM budgets WHERE user_id = :id AND period='monthly' AND start_date = :start",
    ['id'=>$userId, 'start'=>$start]
  );
  if ($existingBudget) {
    $db->update('budgets', [
      'name' => 'Monthly Budget',
      'amount' => $monthly,
      'category_id' => null,
      'period' => 'monthly',
      'start_date' => $start,
      'end_date' => $end,
      'updated_at' => $now,
      'is_active' => true
    ], 'id = :id', ['id' => $existingBudget['id']]);
  } else {
    $db->insert('budgets', [
      'user_id' => $userId,
      'name' => 'Monthly Budget',
      'amount' => $monthly,
      'category_id' => null,
      'period' => 'monthly',
      'start_date' => $start,
      'end_date' => $end,
      'is_active' => true
    ]);
  }

  $db->commit();
  output_json(true, 'Onboarding saved');
} catch(Throwable $e){
  if ($db && $db->getConnection()->inTransaction()) $db->rollback();
  output_json(false, 'Failed to save: '.$e->getMessage());
}
?>
