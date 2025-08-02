<?php

/**
 * Simple dynamic cache clearing tool for SiteGround servers.
 *
 * Based on the core logic of the Speed Optimizer WordPress plugin (by SiteGround) and WordPress Core API,
 * but rewritten as a minimal standalone version without UI or WP dependencies.
 * For non-WordPress environments and internal use only.
 *
 * 本工具參考自 SiteGround Speed Optimizer 插件及的核心清除快取邏輯 WordPress 核心相關 API，
 * 並以獨立方式簡化重寫，無需依賴 WordPress 環境，可直接使用。
 *
 * For personal or internal use. Not affiliated with or endorsed by SiteGround.
 */

# Avoid Siteground agressive dyanmic cache.
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

define('FLUSH_SECRET_TOKEN', "YOUR-OWN-API-KEY"); // ✅ 你自訂的安全 token

// 驗證 token：支援 query, POST 或 Authorization header
function get_request_token()
{
    // 支援 GET / POST
    if (!empty($_GET['token'])) return $_GET['token'];
    if (!empty($_POST['token'])) return $_POST['token'];

    // 支援 Authorization: Bearer xxx
    $headers = getallheaders();

    $auth = null;

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) { // 某些環境會變小寫
            $auth = $headers['authorization'];
        }
    }

    // Nginx / FPM / CLI server fallback
    if (!$auth && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!$auth && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    if (stripos($auth, 'Bearer ') === 0) {
        return trim(substr($auth, 7)); // 直接抽取 Bearer 後面內容
    }

    return null;
}

// 驗證 token
$token = get_request_token();
if ($token !== FLUSH_SECRET_TOKEN) {
    http_response_code(403);
    echo "Access denied: invalid token.";
    exit;
}

// ===== Flush logic 開始 =====

function flush_dynamic_cache($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;
    $hostname = str_replace('www.', '', $host);

    $main_path = parse_url($url, PHP_URL_PATH) ?? '/';
    if (empty($main_path)) $main_path = '/';

    $sock_path = '/chroot/tmp/site-tools.sock';
    $fp = stream_socket_client('unix://' . $sock_path, $errno, $errstr, 5);
    if ($fp === false) {
        return "Socket connection failed: $errstr ($errno)";
    }

    $request = [
        'api'      => 'domain-all',
        'cmd'      => 'update',
        'params'   => [
            'flush_cache' => '1',
            'id'          => $hostname,
            'path'        => $main_path,
        ],
        'settings' => ['json' => 1],
    ];

    fwrite($fp, json_encode($request) . "\n");
    $response = fgets($fp, 32 * 1024);
    fclose($fp);

    return $response ?: 'No response received';
}

// 獲取自定義網址，僅適用於同域名及其子域名
// Get custom URL (works for same domain and subdomains only)
$url = $_GET['url'] ?? null;

if (!$url) {
    // Fallback 取得當前網址
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    $url    = $scheme . '://' . $host . $uri;
}

// 執行 flush
$result = flush_dynamic_cache($url);

// 寫入 log
$log_line = sprintf(
    "[%s] Host: %s | Path: %s | Result: %s\n",
    date('Y-m-d H:i:s'),
    $host,
    $uri,
    trim($result)
);

$log_file = __DIR__ . '/flush.log';
$max_log_size = 500 * 1024; // 500 KB

// Reset if log file is > default 500k.
if (file_exists($log_file) && filesize($log_file) > $max_log_size) {
    $lines = file($log_file);
    $last_lines = array_slice($lines, -100);
    file_put_contents($log_file, "=== flush.log rotated at " . date('Y-m-d H:i:s') . " ===\n" . implode('', $last_lines));
}
file_put_contents($log_file, $log_line, FILE_APPEND);

// 顯示結果
echo "<pre>Flush result:\n$log_line</pre>";
