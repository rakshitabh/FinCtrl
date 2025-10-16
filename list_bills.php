<?php
// List bills for the authenticated user
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/database.php';

function respond($data, $code=200){ http_response_code($code); $buff = ob_get_clean(); if ($buff && trim($buff)!==''){ $data['debug']=$buff; } echo json_encode($data); exit; }

try{
  if (!isset($_SESSION['user']['id'])) respond(['success'=>false,'message'=>'Not authenticated'], 401);
  $db = Database::getInstance();
  $uid = (int)$_SESSION['user']['id'];
  $rows = $db->fetchAll("SELECT id, name, amount, due_date, COALESCE(is_paid, FALSE) AS is_paid FROM bills WHERE user_id = :u ORDER BY due_date ASC, id ASC", ['u'=>$uid]);
  $items = array_map(function($r){
    return [
      'id'=>(int)$r['id'],
      'name'=>$r['name'],
      'amount'=>(float)$r['amount'],
      'dueDate'=>date('Y-m-d', strtotime($r['due_date'])),
      'isPaid'=> (bool)$r['is_paid']
    ];
  }, $rows);
  respond(['success'=>true, 'userId'=>$uid, 'items'=>$items]);
}catch(Throwable $e){ respond(['success'=>false,'message'=>$e->getMessage()],500); }
