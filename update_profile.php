<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/database.php';

function output_json($data){
  ob_clean();
  echo json_encode($data);
  ob_end_flush();
  exit;
}

if (!isset($_SESSION['user']['id'])) { output_json(['success'=>false,'message'=>'Not authenticated']); }

try{
  $db = Database::getInstance();
  $userId = (int)$_SESSION['user']['id'];

  $name = isset($_POST['name']) ? trim($_POST['name']) : '';
  // Email update handled via separate OTP flow; ignore here to support standalone avatar/name updates
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $removeAvatar = isset($_POST['removeAvatar']) && ($_POST['removeAvatar'] === '1' || $_POST['removeAvatar'] === 'true');
  if ($name === '') { output_json(['success'=>false,'message'=>'Invalid name']); }

  // Only update name here; email change requires OTP confirmation via separate flow
  $update = ['name' => $name];

  $avatarUrl = null;
  if (!empty($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
    $file = $_FILES['avatar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
  if (!in_array($ext, $allowed)) { output_json(['success'=>false,'message'=>'Unsupported file type']); }
  if ($file['size'] > 3 * 1024 * 1024) { output_json(['success'=>false,'message'=>'File too large']); }

    $dir = __DIR__ . '/assets/uploads/avatars';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $filename = 'u'.$userId.'_'.time().'.'.$ext;
    $dest = $dir . '/' . $filename;
  if (!move_uploaded_file($file['tmp_name'], $dest)) { output_json(['success'=>false,'message'=>'Upload failed']); }
    // Web path
    $webPath = 'assets/uploads/avatars/' . $filename;
    $avatarUrl = $webPath;
  }

  // Handle avatar removal
  if ($removeAvatar === true) {
    // Try to delete existing file
    $row = $db->fetchOne('SELECT avatar_url FROM user_profiles WHERE user_id = :id', ['id'=>$userId]);
    if ($row && !empty($row['avatar_url'])){
      $path = __DIR__ . '/' . ltrim($row['avatar_url'], '/');
      if (is_file($path)) { @unlink($path); }
    }
    // Upsert to null avatar_url
    $profile = $db->fetchOne('SELECT id FROM user_profiles WHERE user_id = :id', ['id'=>$userId]);
    if ($profile) {
      $db->update('user_profiles', ['avatar_url' => null, 'updated_at'=>date('Y-m-d H:i:s')], 'user_id = :id', ['id'=>$userId]);
    } else {
      $db->insert('user_profiles', [ 'user_id'=>$userId, 'avatar_url'=>null, 'updated_at'=>date('Y-m-d H:i:s') ]);
    }
  }

  $db->update('users', $update, 'id = :id', ['id' => $userId]);

  // Upsert into user_profiles for avatar_url
  if ($avatarUrl !== null) {
    // check if profile exists
    $profile = $db->fetchOne('SELECT id FROM user_profiles WHERE user_id = :id', ['id'=>$userId]);
    if ($profile) {
      $db->update('user_profiles', ['avatar_url' => $avatarUrl, 'updated_at'=>date('Y-m-d H:i:s')], 'user_id = :id', ['id'=>$userId]);
    } else {
      $db->insert('user_profiles', [
        'user_id' => $userId,
        'avatar_url' => $avatarUrl,
        'updated_at' => date('Y-m-d H:i:s')
      ]);
    }
  }

  // Keep session name fresh (email will be updated after OTP confirmation)
  $_SESSION['user']['name'] = $name;

  output_json(['success'=>true,'message'=>'Profile updated']);
} catch (Throwable $e){
  output_json(['success'=>false,'message'=>$e->getMessage()]);
}
?>