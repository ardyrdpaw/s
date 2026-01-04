<?php
include 'db_connect.php';
$users = [];
$result = $conn->query('SELECT id, name, username FROM users');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
echo json_encode(['data' => $users]);
?>