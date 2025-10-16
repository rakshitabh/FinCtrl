<?php
session_start();
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

try{
  $db = Database::getInstance();
  $userId = (int)$_SESSION['user']['id'];
  // Accept JSON or form
  $input = json_decode(file_get_contents('php://input'), true);
  if (!is_array($input)) { $input = $_POST; }
  $id = isset($input['id']) ? (int)$input['id'] : 0;
  if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid transaction id']); exit; }

  $row = $db->fetchOne("SELECT id FROM transactions WHERE id = :id AND user_id = :uid", ['id'=>$id,'uid'=>$userId]);
  if (!$row) { echo json_encode(['success'=>false,'message'=>'Transaction not found']); exit; }

  $db->delete('transactions', 'id = :id', ['id'=>$id]);
  echo json_encode(['success'=>true]);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
