<?php
require __DIR__.'/../app/db.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if(!preg_match("/^[a-zA-Z\s]+$/",$name)) die("Invalid Name");
if(!filter_var($email,FILTER_VALIDATE_EMAIL)) die("Invalid Email");
if(empty($message)) die("Message cannot be empty");

$stmt = $pdo->prepare("INSERT INTO contacts(name,email,message) VALUES(?,?,?)");
$stmt->execute([$name,$email,$message]);

echo "Message sent successfully!";
