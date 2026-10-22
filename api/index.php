<?php
/*
 * ============================================================
 * Truecaller Phone Number Lookup API
 * Developed By: https://t.me/Infinity_Codex
 * ============================================================
 */

// Error reporting configuration
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

// ============================================================
// Rate Limit System (6 Seconds Cooldown)
// Developed By https://t.me/Infinity_Codex
// ============================================================
$rate_limit_seconds = 6;
$lock_file = sys_get_temp_dir() . '/tc_rate_limit.txt';
$current_time = microtime(true);

if (file_exists($lock_file)) {
    $last_request_time = (float)file_get_contents($lock_file);
    $elapsed = $current_time - $last_request_time;
    if ($elapsed < $rate_limit_seconds) {
        $wait_time = ceil($rate_limit_seconds - $elapsed);
        http_response_code(429);
        echo json_encode([
            "success" => false,
            "message" => "Too many requests. Please wait {$wait_time} seconds before trying again.",
            "developer" => "https://t.me/Infinity_Codex",
            "data" => null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// Save current request timestamp
file_put_contents($lock_file, (string)$current_time, LOCK_EX);

// ============================================================
// Input Validation & Phone Number Parsing
// ============================================================
$raw_phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$clean_digits = preg_replace('/[^\d]/', '', $raw_phone);

if (strlen($clean_digits) < 7) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please provide a valid phone number with country code (e.g. +8801965064030).",
        "developer" => "https://t.me/Infinity_Codex",
        "data" => null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Helper function to detect country code and format number
// Developed By https://t.me/Infinity_Codex
function parse_phone_simple($digits) {
    $countries = [
        '880' => ['iso' => 'bd', 'name' => 'Bangladesh'],
        '91'  => ['iso' => 'in', 'name' => 'India'],
        '92'  => ['iso' => 'pk', 'name' => 'Pakistan'],
        '261' => ['iso' => 'mg', 'name' => 'Madagascar'],
        '966' => ['iso' => 'sa', 'name' => 'Saudi Arabia'],
        '971' => ['iso' => 'ae', 'name' => 'UAE'],
        '60'  => ['iso' => 'my', 'name' => 'Malaysia'],
        '65'  => ['iso' => 'sg', 'name' => 'Singapore'],
        '44'  => ['iso' => 'gb', 'name' => 'United Kingdom'],
        '1'   => ['iso' => 'us', 'name' => 'USA/Canada'],
    ];

    foreach ($countries as $code => $info) {
        if (strpos($digits, (string)$code) === 0) {
            return [$info['iso'], substr($digits, strlen($code)), $info['name']];
        }
    }

    if (strpos($digits, '01') === 0 && strlen($digits) === 11) {
        return ['bd', substr($digits, 1), 'Bangladesh'];
    }

    return ['bd', $digits, 'Unknown'];
}

list($country_code, $clean_number, $country_name) = parse_phone_simple($clean_digits);

// ============================================================
// Method 1: Truecaller Direct Mobile API Lookup
// Developed By https://t.me/Infinity_Codex
// ============================================================
$bearer_token = 'a1w0A-0-jz3A0VwkqpROqM9qPW4yW7MsgKrjzAF8Wxx1Ps7dMVxaBMb5g8NvCttW';

$api_url = "https://search5-noneu.truecaller.com/v2/search?q={$clean_number}&countryCode=" . strtoupper($country_code) . "&type=4&locAddr=&placement=SEARCHRESULTS,HISTORY,DETAILS&encoding=json";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $bearer_token,
        "User-Agent: Truecaller/13.36.6 (Android;12)"
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$api_response = curl_exec($ch);
$api_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($api_http_code === 200 && $api_response) {
    $json_data = json_decode($api_response, true);
    if (!empty($json_data['data'][0])) {
        $info = $json_data['data'][0];
        echo json_encode([
            "success" => true,
            "developer" => "https://t.me/Infinity_Codex",
            "data" => [
                "phone" => $clean_number,
                "country_code" => $country_code,
                "name" => $info['name'] ?? null,
                "carrier" => $info['phones'][0]['carrier'] ?? null,
                "address" => $info['addresses'][0]['city'] ?? $country_name,
                "image" => $info['image'] ?? null
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// ============================================================
// Method 2: Fallback Web Scraping with Cookies
// Developed By https://t.me/Infinity_Codex
// ============================================================
$url = "https://www.truecaller.com/search/{$country_code}/{$clean_number}";

$cookies = 'tc_user_analytics_id=86eaedf7-3fb0-442b-86b3-91ebef6712a6; tc:dismisslanguagesuggestion=true; tc_user=%7B%22token%22%3A%22eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3OTUxODI1OTI0NDMsInRva2VuIjoiYTF3MEEtMC1qejNBMFZ3a3FwUk9xTTlxUFc0eVc3TXNnS3JqekFGOFd4eDFQczdkTVZ4YUJNYjVnOE52Q3R0VyIsImNvdW50cnlDb2RlIjoiYmQiLCJuYW1lIjoiUmlhZCBhbCBIYXNhbiIsImltYWdlIjoiaHR0cHM6Ly9pbWFnZXMtbm9uZXUudHJ1ZWNhbGxlcnN0YXRpYy5jb20vbXl2aWV3LzIvNGI5MDQ1ZDE5MGNmNDFhOTQwYTFmYTlkMjE0NGMyZTAvMyIsImlzQXBwQWNjb3VudCI6dHJ1ZSwiaWF0IjoxNzkyNTA0MTkyfQ.x6Q17RAgvon8np1CGRhoc0Uu3GgCzhcMFxqzYP96byA%22%7D; tc:searches=%7B%22searchType%22%3A%2244%22%2C%22bottomSheetDisplayed%22%3A1792504115957%2C%22e3b0c44%22%3A%7B%2244%22%3A11%2C%22country%22%3A%22bd%22%7D%7D';

$headers = [
    'User-Agent: Mozilla/5.0 (Linux; Android 12; V2027 Build/SP1A.210812.003) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.8059.43 Mobile Safari/537.36',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language: en-US,en;q=0.9',
    'x-requested-with: mark.via.gp'
];

$ch2 = curl_init();
curl_setopt_array($ch2, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_TIMEOUT => 20,
    CURLOPT_COOKIE => $cookies,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$html_text = curl_exec($ch2);
$http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch2);

if ($curl_err) {
    echo json_encode([
        "success" => false,
        "message" => "cURL error: " . $curl_err,
        "developer" => "https://t.me/Infinity_Codex"
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($http_code !== 200) {
    echo json_encode([
        "success" => false, 
        "message" => "Truecaller blocked the request or token expired.",
        "api_status" => $api_http_code,
        "web_status" => $http_code,
        "developer" => "https://t.me/Infinity_Codex"
    ], JSON_PRETTY_PRINT);
    exit;
}

// ============================================================
// Parse HTML Data (Name, Carrier, Image)
// ============================================================
$name = null;
if (preg_match('/class=["\'][^"\']*break-all[^"\']*["\'][^>]*>\s*([^<]+?)\s*<\/div>/i', $html_text, $matches)) {
    $val = trim($matches[1]);
    if (!empty($val) && !preg_match('/^\+?[0-9\s\-]+$/', $val)) {
        $name = $val;
    }
}

$carrier = null;
if (preg_match('/(?:M\s*-\s*|Carrier\s*:\s*)([^<>\n\r&]+?)(?:<\/(?:div|span|p)>|<br|Address|&quot;)/i', $html_text, $matches)) {
    $clean_c = trim(strip_tags($matches[1]));
    $clean_c = preg_replace('/\s*(?:Digital\s+Communications\s+Ltd\.?|Axiata\s+Ltd\.?|Telecom\s+Ltd\.?|Bangladesh\s+Ltd\.?|Ltd\.?)\s*$/i', '', $clean_c);
    $carrier = trim($clean_c);
}

$image = null;
if (preg_match('/src="(https:\/\/images-[^"]+?truecallerstatic\.com\/myview\/[^"]+?)"/i', $html_text, $matches)) {
    $image = $matches[1];
}

// ============================================================
// Final JSON Response Output
// Developed By https://t.me/Infinity_Codex
// ============================================================
echo json_encode([
    "success" => true,
    "developer" => "https://t.me/Infinity_Codex",
    "data" => [
        "phone" => $clean_number,
        "country_code" => $country_code,
        "name" => $name ? html_entity_decode($name) : null,
        "carrier" => $carrier,
        "address" => $country_name,
        "image" => $image
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
