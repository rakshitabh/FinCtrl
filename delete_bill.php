<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/database.php';

function respond($data, $code=200){ http_response_code($code); $out = ob_get_clean(); if ($out && trim($out)!==''){ $data['debug']=$out; } echo json_encode($data); exit; }

try{
  if (!isset($_SESSION['user']['id'])) respond(['success'=>false,'message'=>'Not authenticated'],401);
  $raw = file_get_contents('php://input');
  $input = json_decode($raw,true);
  $id = isset($input['id']) ? (int)$input['id'] : 0;
  if ($id<=0) respond(['success'=>false,'message'=>'Invalid id'],422);
  $db = Database::getInstance(); $uid=(int)$_SESSION['user']['id'];
  $b = $db->fetchOne('SELECT id FROM bills WHERE id = :id AND user_id = :u', ['id'=>$id,'u'=>$uid]);
  if (!$b) respond(['success'=>false,'message'=>'Not found'],404);
  $db->delete('bills', 'id = :id AND user_id = :u', ['id'=>$id,'u'=>$uid]);
  respond(['success'=>true]);
}catch(Throwable $e){ respond(['success'=>false,'message'=>$e->getMessage()],500); }
