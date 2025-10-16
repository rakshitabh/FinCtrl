<?php
session_start();
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

try{
  $db = Database::getInstance();
  $uid = (int)$_SESSION['user']['id'];
  $data = json_decode(file_get_contents('php://input'), true);
  if (!is_array($data)) $data = $_POST;

  $id = isset($data['id']) ? (int)$data['id'] : 0;
  $date = $data['date'] ?? null;
  $type = $data['type'] ?? null; // 'expense' | 'income'
  $categoryId = isset($data['categoryId']) ? (int)$data['categoryId'] : null;
  $amount = isset($data['amount']) ? (float)$data['amount'] : null;
  $description = $data['description'] ?? null;

  if ($id <= 0 || !$date || !$type || !$categoryId || !$amount) {
    echo json_encode(['success'=>false,'message'=>'Missing or invalid fields']); exit;
  }

  $existing = $db->fetchOne("SELECT id FROM transactions WHERE id = :id AND user_id = :uid", ['id'=>$id, 'uid'=>$uid]);
  if (!$existing) { echo json_encode(['success'=>false,'message'=>'Transaction not found']); exit; }

  $db->update('transactions', [
    'transaction_date' => $date,
    'type' => $type,
    'category_id' => $categoryId,
    'amount' => $amount,
    'description' => $description
  ], 'id = :id', ['id'=>$id]);

  echo json_encode(['success'=>true]);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
