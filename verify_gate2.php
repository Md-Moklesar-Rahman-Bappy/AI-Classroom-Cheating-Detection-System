<?php
$db = new PDO('mysql:host=localhost;dbname=ai_classroom;charset=utf8mb4', 'root', '');
$c = $db->query("SELECT COUNT(*) as c FROM users JOIN role_user ON users.id=role_user.user_id JOIN roles ON roles.id=role_user.role_id WHERE roles.name='system_admin' AND users.deleted_at IS NULL")->fetch();
echo "Active system_admin count: " . $c['c'] . PHP_EOL;
