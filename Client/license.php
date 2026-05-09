<?php

require_once 'env.php';

// ================= LICENSE CONFIG =================
$verifyServer =
    'https://protechweb.io.vn/license/verify.php';

// ================= GET KEY =================
$licenseKey =
    trim(
        $_ENV['LICENSE_KEY'] ?? ''
    );

// ================= CHECK =================
if (
    empty($licenseKey)
) {

    die('License key missing');
}

// ================= DOMAIN =================
$currentDomain =
    $_SERVER['HTTP_HOST'];

// ================= CREATE URL =================
$url =
    $verifyServer .
    '?key=' . urlencode($licenseKey) .
    '&domain=' . urlencode($currentDomain);

// ================= CURL =================
$ch = curl_init($url);

curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);

curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    15
);

curl_setopt(
    $ch,
    CURLOPT_SSL_VERIFYPEER,
    false
);

$response =
    curl_exec($ch);

$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

curl_close($ch);

// ================= SERVER ERROR =================
if (
    $response === false ||
    $httpCode !== 200
) {

    die('License server offline');
}

// ================= JSON =================
$result =
    json_decode(
        $response,
        true
    );

// ================= INVALID RESPONSE =================
if (
    !$result ||
    !isset($result['valid'])
) {

    die('License response invalid');
}

// ================= INVALID LICENSE =================
if (
    $result['valid'] !== true
) {

    die(
        'License Error: ' .
        ($result['message'] ?? 'Unknown')
    );
}

// ================= SUCCESS =================
define(
    'LICENSE_VALID',
    true
);