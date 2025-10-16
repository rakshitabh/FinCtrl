<?php
session_start();
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

try{
  $db = Database::getInstance();
  $userId = (int)$_SESSION['user']['id'];
  $input = json_decode(file_get_contents('php://input'), true);
  if (!is_array($input)) { $input = $_POST; }
  $amount = isset($input['amount']) ? (float)$input['amount'] : 0.0;
  if ($amount <= 0) { echo json_encode(['success'=>false,'message'=>'Amount must be greater than 0']); exit; }

  $start = date('Y-m-01');
  // Upsert monthly budget for the current month
  $existing = $db->fetchOne("SELECT id FROM budgets WHERE user_id=:uid AND period='monthly' AND start_date=:start", ['uid'=>$userId,'start'=>$start]);
  if ($existing) {
    $db->update('budgets', [ 'amount'=>$amount, 'name'=>'Monthly Budget', 'updated_at'=>date('Y-m-d H:i:s') ], 'id = :id', ['id'=>$existing['id']]);
  } else {
    $db->insert('budgets', [ 'user_id'=>$userId, 'name'=>'Monthly Budget', 'period'=>'monthly', 'amount'=>$amount, 'start_date'=>$start, 'created_at'=>date('Y-m-d H:i:s'), 'updated_at'=>date('Y-m-d H:i:s') ]);
  }
  echo json_encode(['success'=>true]);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
