<?php
require_once __DIR__ . '/includes/config.php';

// Unset session arrays
$_SESSION = array();

// Destroy active cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session context
session_destroy();

// Redirect to home
header("Location: index.php");
exit;
?>
