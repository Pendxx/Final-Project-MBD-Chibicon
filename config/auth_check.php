<?php
// config/auth_check.php
// Include this file on every protected page AFTER config/db.php.
// If the user is not logged in, they are redirected to login.php.

if (!isset($_SESSION['user'])) {
    $_SESSION['login_error'] = 'Silakan login terlebih dahulu.';
    // Build an absolute path back to login.php regardless of subfolder depth
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    // Walk up to MBD root if we are inside a subfolder (e.g. /MBD/auth/)
    // login.php always lives at the project root
    $script_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $mbd_root   = realpath(__DIR__ . '/..');          // MBD/
    $rel_depth  = substr_count(
        str_replace($mbd_root, '', $script_dir), DIRECTORY_SEPARATOR
    );
    $prefix = str_repeat('../', max(0, $rel_depth));
    header('Location: ' . $prefix . 'login.php');
    exit;
}

// Make current user available as a global shorthand
$current_user = $_SESSION['user'];
