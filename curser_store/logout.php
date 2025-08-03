<?php
require_once 'config/database.php';

// Destroy the session
session_destroy();
 
// Redirect to home page
header('Location: index.php');
exit;
?> 