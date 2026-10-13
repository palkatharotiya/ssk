<?php
/**
 * BAPS Store - Admin Logout
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

logout_admin();
header('Location: login.php');
exit;
