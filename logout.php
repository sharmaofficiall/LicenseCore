<?php
require_once 'includes/enhanced.php';

// Destroy session and logout
session_destroy();

// Redirect to login page
header('Location: login/');
exit;
