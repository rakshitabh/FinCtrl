<?php
session_start();
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user']['id'])) { echo json_encode(['categories'=>[]]); exit; }
try{
  $db = Database::getInstance();
  $uid = (int)$_SESSION['user']['id'];
  // Fetch default + user categories
  $rows = $db->fetchAll("SELECT id, name FROM categories WHERE is_default = TRUE OR user_id = :id ORDER BY name ASC", ['id'=>$uid]);
  echo json_encode(['categories'=>$rows]);
} catch(Throwable $e){ echo json_encode(['categories'=>[]]); }
?>
