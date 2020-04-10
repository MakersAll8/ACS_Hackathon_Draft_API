<?php
namespace Api;
require_once 'init.php';

Util::requestType('post');
$util = Util::inputIsJSON();
$input = $util->getInput();

$username = $input['username'];
$password = $input['password'];
$confirmPassword = $input['confirmPassword'];


if(empty($username) || empty($password) || empty($confirmPassword)){
    $message = ['error'=>'username, password, and confirm password cannot be empty'];
    Util::respond($message);
}

if($password!==$confirmPassword){
    $message = ['error'=>'password and confirm password do not match'];
    Util::respond($message);
}

$pdo = CommunityDB::pdo();
$pdo->beginTransaction();
$sql = 'SELECT * FROM User WHERE username = ? FOR UPDATE ';
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
if($stmt->fetch()){
    $pdo->rollBack();
    $message = ['error'=>'username '.$username.' is already taken'];
    Util::respond($message);
}

// create password
$options = ['cost' => 12];
$encryptedPassword = password_hash($password, PASSWORD_BCRYPT, $options);

$sql = 'INSERT INTO User(`username`, `password`) VALUE (?, ?)';
$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $encryptedPassword]);
$userId = $pdo->lastInsertId(); // must be called ahead of commit, otherwise,
// id is 0
$pdo->commit();

$message = ['success'=>'username '.$username.' is created with user id '
    .$userId];
Util::respond($message);
