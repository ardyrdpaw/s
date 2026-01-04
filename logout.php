<?php
include_once __DIR__ . '/php/auth.php';
// require login before allowing logout
require_login();
session_destroy();
header('Location: login.php');
exit;
