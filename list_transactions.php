<?php
session_start();
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

try{
  $db = Database::getInstance();
  $uid = (int)$_SESSION['user']['id'];

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $from = $_GET['from'] ?? null;
  $to = $_GET['to'] ?? null;
  $categoryId = isset($_GET['categoryId']) ? (int)$_GET['categoryId'] : 0;
  $min = isset($_GET['min']) ? (float)$_GET['min'] : null;
  $max = isset($_GET['max']) ? (float)$_GET['max'] : null;

  $where = ['t.user_id = :uid'];
  $params = ['uid'=>$uid];

  if ($id > 0) { $where[] = 't.id = :id'; $params['id'] = $id; }
  if ($from) { $where[] = 't.transaction_date >= :from'; $params['from'] = $from; }
  if ($to) { $where[] = 't.transaction_date <= :to'; $params['to'] = $to; }
  if ($categoryId > 0) { $where[] = 't.category_id = :cid'; $params['cid'] = $categoryId; }
  if ($min !== null) { $where[] = 't.amount >= :min'; $params['min'] = $min; }
  if ($max !== null) { $where[] = 't.amount <= :max'; $params['max'] = $max; }

  $sql = "SELECT t.id, t.transaction_date, t.type, t.amount, t.description, c.id as category_id, COALESCE(c.name,'Other') as category
          FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
          WHERE ".implode(' AND ', $where)." ORDER BY t.transaction_date DESC, t.id DESC LIMIT 500";

  $rows = $db->fetchAll($sql, $params);
  $items = array_map(function($r){
    return [
      'id' => (int)$r['id'],
      'date' => date('Y-m-d', strtotime($r['transaction_date'])),
      'type' => $r['type'],
      'categoryId' => $r['category_id'] ? (int)$r['category_id'] : null,
      'category' => $r['category'],
      'amount' => (float)$r['amount'],
      'amountFormatted' => number_format((float)$r['amount'], 2),
      'description' => $r['description'] ?: ''
    ];
  }, $rows);

  echo json_encode(['success'=>true, 'items'=>$items]);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
