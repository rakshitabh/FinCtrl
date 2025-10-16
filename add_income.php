<?php
// UTF-8 without BOM
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/database.php';

function respond(array $d, int $code=200){ http_response_code($code); $o=ob_get_clean(); if($o&&trim($o)!=='') $d['debug']=$o; echo json_encode($d); exit; }

try{
  if (!isset($_SESSION['user']['id'])) respond(['success'=>false,'message'=>'Not authenticated'],401);
  $raw = file_get_contents('php://input');
  $input = json_decode($raw,true);
  if (!is_array($input)) respond(['success'=>false,'message'=>'Invalid JSON body'],400);
  $date = $input['date'] ?? null;
  $categoryId = isset($input['categoryId']) ? (int)$input['categoryId'] : 0;
  $amount = isset($input['amount']) ? (float)$input['amount'] : 0.0;
  $description = isset($input['description']) ? trim((string)$input['description']) : null;
  if (!$date || $categoryId<=0 || $amount<=0) respond(['success'=>false,'message'=>'Missing or invalid fields'],422);
  try{ $dt = new DateTime($date); $date = $dt->format('Y-m-d'); } catch(Throwable $e){ respond(['success'=>false,'message'=>'Invalid date format'],422); }

  $db = Database::getInstance();
  $uid = (int)$_SESSION['user']['id'];
  $acc = $db->fetchOne('SELECT id, currency FROM accounts WHERE user_id = :u AND (is_active = true OR is_active IS NULL) ORDER BY id ASC LIMIT 1', ['u'=>$uid]);
  if ($acc) { $accId = (int)$acc['id']; }
  else {
    $profile = $db->fetchOne('SELECT currency FROM user_profiles WHERE user_id = :u', ['u'=>$uid]);
    $accId = (int)$db->insert('accounts', [ 'user_id'=>$uid, 'name'=>'Cash', 'type'=>'cash', 'currency'=>($profile['currency']??'USD'), 'balance'=>0.00, 'is_active'=>true ]);
  }
  $cat = $db->fetchOne('SELECT id FROM categories WHERE id = :c AND (user_id = :u OR user_id IS NULL)', ['c'=>$categoryId,'u'=>$uid]);
  if (!$cat) respond(['success'=>false,'message'=>'Invalid category'],422);

  $txId = (int)$db->insert('transactions', [
    'user_id'=>$uid,
    'account_id'=>$accId,
    'category_id'=>$categoryId,
    'amount'=>$amount,
    'type'=>'income',
    'description'=>$description,
    'transaction_date'=>$date,
  ]);
  try { $db->query('UPDATE accounts SET balance = COALESCE(balance,0) + :amt WHERE id = :aid', ['amt'=>$amount,'aid'=>$accId]); } catch(Throwable $e){}
  respond(['success'=>true,'id'=>$txId,'message'=>'Income added']);
} catch(Throwable $e){ respond(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()],500); }
