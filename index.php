<?php
// Main entry point for Support Apps BKPSDM
include_once __DIR__ . '/php/auth.php';
require_login();
header('Location: dashboard.php');
exit;