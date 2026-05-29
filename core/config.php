<?php
$supabaseUrl = getenv('SUPABASE_URL') ?: 'https://pusrebyszbtyefcjmvsh.supabase.co';
$supabaseKey = getenv('SUPABASE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB1c3JlYnlzemJ0eWVmY2ptdnNoIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3OTIxNjgzOCwiZXhwIjoyMDk0NzkyODM4fQ.UUbI-8OuKFxE6lMja0s8SYNMkSpJD2V2lwv2rjEa0kk';
$adminUser = getenv('ADMIN_USER') ?: 'admin';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';

$localConfigPath = dirname(__DIR__, 2) . '/config.local.php';
if (file_exists($localConfigPath)) {
    $localConfig = include $localConfigPath;
    if (is_array($localConfig)) {
        $supabaseUrl = $localConfig['SUPABASE_URL'] ?? $supabaseUrl;
        $supabaseKey = $localConfig['SUPABASE_KEY'] ?? $supabaseKey;
        $adminUser = $localConfig['ADMIN_USER'] ?? $adminUser;
        $adminPassword = $localConfig['ADMIN_PASSWORD'] ?? $adminPassword;
    }
}

define('SUPABASE_URL', rtrim($supabaseUrl ?: '', '/'));
define('SUPABASE_KEY', $supabaseKey ?: '');
define('ADMIN_USER', $adminUser);
define('ADMIN_PASSWORD', $adminPassword);

$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
    || (isset($_SERVER['HTTP_HOST']) && preg_match('/(localhost|127\.0\.0\.1|\.local|\.test)$/i', $_SERVER['HTTP_HOST']));

if (!defined('SUPABASE_SSL_VERIFY')) {
    define('SUPABASE_SSL_VERIFY', !$isLocalhost);
}
