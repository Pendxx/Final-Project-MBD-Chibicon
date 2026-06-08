<?php
// config/google.php - Google OAuth 2.0 Configuration
// ─────────────────────────────────────────────────────────────────────────────
// SETUP INSTRUCTIONS:
// 1. Go to: https://console.cloud.google.com/
// 2. Create a new project (or select existing)
// 3. Enable "Google+ API" / "People API"
// 4. Go to Credentials → Create Credentials → OAuth 2.0 Client ID
// 5. Application type: Web application
// 6. Add Authorized redirect URI: http://localhost:PORT/MBD/auth/callback.php
//    (adjust PORT: 8888 for MAMP, 80/no port for XAMPP, your Herd port, etc.)
// 7. Copy the Client ID and Client Secret below
// ─────────────────────────────────────────────────────────────────────────────

define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');

// Change this to match your exact server address & port
define('GOOGLE_REDIRECT_URI',  'http://localhost:8080/MBD/auth/callback.php');

// Scopes to request from Google
define('GOOGLE_SCOPES', 'openid email profile');
