<?php
session_start();
require_once __DIR__ . '/includes/database.php';

if (!isset($_SESSION['user']['id'])) { http_response_code(401); echo 'Not authenticated'; exit; }

try{
  $db = Database::getInstance();
  $uid = (int)$_SESSION['user']['id'];

  $from = $_GET['from'] ?? null;
  $to = $_GET['to'] ?? null;
  $categoryId = isset($_GET['categoryId']) ? (int)$_GET['categoryId'] : 0;
  $min = isset($_GET['min']) ? (float)$_GET['min'] : null;
  $max = isset($_GET['max']) ? (float)$_GET['max'] : null;

  $where = ['t.user_id = :uid'];
  $params = ['uid'=>$uid];

  if ($from) { $where[] = 't.transaction_date >= :from'; $params['from'] = $from; }
  if ($to) { $where[] = 't.transaction_date <= :to'; $params['to'] = $to; }
  if ($categoryId > 0) { $where[] = 't.category_id = :cid'; $params['cid'] = $categoryId; }
  if ($min !== null) { $where[] = 't.amount >= :min'; $params['min'] = $min; }
  if ($max !== null) { $where[] = 't.amount <= :max'; $params['max'] = $max; }

  $sql = "SELECT t.transaction_date, t.type, COALESCE(c.name,'Other') as category, t.amount, t.description
          FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
          WHERE ".implode(' AND ', $where)." ORDER BY t.transaction_date DESC, t.id DESC";
  $rows = $db->fetchAll($sql, $params);

  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="transactions.csv"');

  // Add UTF-8 BOM for Excel compatibility
  echo "\xEF\xBB\xBF";
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Date','Type','Category','Amount','Description']);
  foreach ($rows as $r) {
    $date = date('Y-m-d', strtotime($r['transaction_date']));
    $amount = (string)round((float)$r['amount'], 2);
    // Prefix with tab to force Excel to treat as text and avoid ##### display
    fputcsv($out, [
      "\t".$date,
      $r['type'],
      $r['category'],
      "\t".$amount,
      $r['description']
    ]);
  }
  fclose($out);
} catch (Throwable $e){
  http_response_code(500);
  echo 'Export failed: '.$e->getMessage();
}
