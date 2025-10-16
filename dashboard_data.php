<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) { echo json_encode(['error'=>'Not authenticated']); exit; }

try{
  $db = Database::getInstance();
  $userId = (int)$_SESSION['user']['id'];

  // Budget for current month
  $start = date('Y-m-01');
  $end = date('Y-m-t');
  $budget = $db->fetchOne("SELECT amount FROM budgets WHERE user_id = :id AND period='monthly' AND start_date = :start", ['id'=>$userId, 'start'=>$start]);
  $totalBudget = $budget ? (float)$budget['amount'] : 0.0;

  // Expenses for current month
  $row = $db->fetchOne("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id = :id AND type='expense' AND transaction_date BETWEEN :start AND :end", ['id'=>$userId,'start'=>$start,'end'=>$end]);
  $totalExpenses = $row ? (float)$row['total'] : 0.0;
  $remaining = $totalBudget - $totalExpenses;

  // Upcoming bill (next one after today)
  $bill = $db->fetchOne("SELECT name, amount, due_date FROM bills WHERE user_id = :id AND due_date >= CURRENT_DATE ORDER BY due_date ASC LIMIT 1", ['id'=>$userId]);
  $upcomingBill = $bill ? ($bill['name'].' - '.number_format((float)$bill['amount'],2).' due '.date('M d', strtotime($bill['due_date']))) : null;

  // Bills due (pending bills for current month)
  $billsDueRow = $db->fetchOne(
    "SELECT COUNT(*) AS cnt FROM bills WHERE user_id = :id AND is_paid = FALSE AND due_date BETWEEN :start AND :end",
    ['id'=>$userId,'start'=>$start,'end'=>$end]
  );
  $billsDue = $billsDueRow ? (int)$billsDueRow['cnt'] : 0;

  // Recent transactions (last 10)
  $rows = $db->fetchAll("SELECT t.id, t.transaction_date, t.type, c.name as category, t.amount, t.description FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = :id ORDER BY t.transaction_date DESC, t.id DESC LIMIT 10", ['id'=>$userId]);
  $recent = array_map(function($r){
    return [
      'id' => (int)$r['id'],
      'date' => date('Y-m-d', strtotime($r['transaction_date'])),
      'type' => $r['type'] ?? 'expense',
      'category' => $r['category'] ?: '-',
      'amount' => (float)$r['amount'],
      'amountFormatted' => number_format((float)$r['amount'], 2),
      'description' => $r['description'] ?: ''
    ];
  }, $rows);

  // Category breakdown (current month)
  $cat = $db->fetchAll("SELECT COALESCE(c.name,'Other') as category, COALESCE(SUM(t.amount),0) as total FROM transactions t LEFT JOIN categories c ON c.id=t.category_id WHERE t.user_id=:id AND t.type='expense' AND t.transaction_date BETWEEN :start AND :end GROUP BY category ORDER BY total DESC LIMIT 6", ['id'=>$userId,'start'=>$start,'end'=>$end]);
  $labels = array_column($cat, 'category');
  $values = array_map('floatval', array_column($cat, 'total'));

  // Monthly trend (last 6 months)
  $monthly = [];
  for ($i=5; $i>=0; $i--) {
    $mStart = date('Y-m-01', strtotime("-$i month"));
    $mEnd = date('Y-m-t', strtotime($mStart));
    $row = $db->fetchOne("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id=:id AND type='expense' AND transaction_date BETWEEN :start AND :end", ['id'=>$userId,'start'=>$mStart,'end'=>$mEnd]);
    $monthly[] = ['label'=>date('M Y', strtotime($mStart)), 'value'=>(float)$row['total']];
  }

  // Savings overview (aggregate of active goals)
  // Savings overview: prefer active goals aggregate; if none active but there is a completed goal, show last completed as 100% instead of 0/0.
  $sav = $db->fetchOne("SELECT COALESCE(SUM(current_amount),0) AS cur, COALESCE(SUM(target_amount),0) AS tgt FROM savings_goals WHERE user_id = :id AND completed = FALSE", ['id'=>$userId]);
  $sCur = $sav ? (float)$sav['cur'] : 0.0;
  $sTgt = $sav ? (float)$sav['tgt'] : 0.0;
  if ($sCur <= 0 && $sTgt <= 0) {
    $last = $db->fetchOne("SELECT current_amount, target_amount FROM savings_goals WHERE user_id = :id AND completed = TRUE ORDER BY updated_at DESC, id DESC LIMIT 1", ['id'=>$userId]);
    if ($last) {
      $sCur = (float)$last['current_amount'];
      $sTgt = (float)$last['target_amount'];
      if ($sTgt > 0 && $sCur >= $sTgt) { $sCur = $sTgt; }
    }
  }
  $sPct = ($sTgt > 0) ? min(100, max(0, round(($sCur/$sTgt)*100))) : 0;

  echo json_encode([
    'totalBudget' => $totalBudget,
    'totalBudgetFormatted' => number_format($totalBudget,2),
    'totalExpenses' => $totalExpenses,
    'totalExpensesFormatted' => number_format($totalExpenses,2),
    'remainingBudget' => $remaining,
    'remainingBudgetFormatted' => number_format($remaining,2),
    'upcomingBill' => $upcomingBill,
    'billsDue' => $billsDue,
    'recentTransactions' => $recent,
    'categoryBreakdown' => [ 'labels'=>$labels, 'values'=>$values ],
    'monthly' => [ 'labels'=>array_column($monthly,'label'), 'values'=>array_column($monthly,'value') ],
    'savings' => [
      'current' => $sCur,
      'target' => $sTgt,
      'percent' => $sPct,
      'currentFormatted' => number_format($sCur,2),
      'targetFormatted' => number_format($sTgt,2)
    ]
  ]);
} catch (Throwable $e){
  echo json_encode(['error'=>true,'message'=>$e->getMessage()]);
}
?>

