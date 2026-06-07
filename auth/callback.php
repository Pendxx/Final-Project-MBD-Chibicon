<?php
// auth/callback.php - Google OAuth 2.0 Callback Handler
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/google.php';

// Verify state token to prevent CSRF
if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    $_SESSION['login_error'] = 'Sesi tidak valid. Silakan coba lagi.';
    header('Location: ../login.php');
    exit;
}
unset($_SESSION['oauth_state']);

// Handle error from Google
if (isset($_GET['error'])) {
    $_SESSION['login_error'] = 'Login Google dibatalkan: ' . htmlspecialchars($_GET['error']);
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['code'])) {
    $_SESSION['login_error'] = 'Kode otorisasi tidak ditemukan.';
    header('Location: ../login.php');
    exit;
}

// Exchange authorization code for access token
$token_data = google_request('https://oauth2.googleapis.com/token', [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (!isset($token_data['access_token'])) {
    $_SESSION['login_error'] = 'Gagal mendapatkan token akses dari Google.';
    header('Location: ../login.php');
    exit;
}

// Fetch user profile from Google
$profile = google_get('https://www.googleapis.com/oauth2/v2/userinfo', $token_data['access_token']);

if (!isset($profile['email'])) {
    $_SESSION['login_error'] = 'Gagal mendapatkan info profil dari Google.';
    header('Location: ../login.php');
    exit;
}

// Store user data in session
$_SESSION['user'] = [
    'id'      => $profile['id'],
    'name'    => $profile['name'],
    'email'   => $profile['email'],
    'avatar'  => $profile['picture'] ?? '',
    'login'   => 'google',
];

$_SESSION['toast']      = 'Selamat datang, ' . $profile['given_name'] . '! 👋';
$_SESSION['toast_type'] = 'success';

header('Location: ../index.php');
exit;

// ── Helpers ────────────────────────────────────────────────────────────────────

function google_request(string $url, array $post): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($post),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

function google_get(string $url, string $access_token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}
