<?php
function envv(string $key): string {
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? '';
    }
    return is_string($v) ? trim($v) : '';
}

define('GOOGLE_CLIENT_ID', envv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', envv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', envv('GOOGLE_REDIRECT_URI'));
define('SITE_URL', envv('SITE_URL'));
define('TELEGRAM_BOT_TOKEN', envv('TELEGRAM_BOT_TOKEN'));
define('TELEGRAM_CHAT_ID', envv('TELEGRAM_CHAT_ID'));
