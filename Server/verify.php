<?php

header('Content-Type: application/json');

require_once 'env.php';

// ================= CONNECT DB =================
try {

    $pdo = new PDO(

        "mysql:host=".$_ENV['DB_HOST'].
        ";dbname=".$_ENV['DB_NAME'].
        ";charset=utf8mb4",

        $_ENV['DB_USER'],

        $_ENV['DB_PASS']
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    echo json_encode([
        'valid' => false,
        'message' => 'Database error'
    ]);

    exit;
}

// ================= GET DATA =================
$key =
    $_GET['key'] ?? '';

$domain =
    $_GET['domain'] ?? '';

// ================= CHECK =================
$stmt = $pdo->prepare("
    SELECT *
    FROM licenses
    WHERE license_key = ?
    LIMIT 1
");

$stmt->execute([$key]);

$license =
    $stmt->fetch(PDO::FETCH_ASSOC);

if (!$license) {

    echo json_encode([
        'valid' => false,
        'message' => 'Invalid key'
    ]);

    exit;
}

// ================= STATUS =================
if (
    trim(strtolower($license['STATUS'])) !== 'active'
) {

    echo json_encode([
        'valid' => false,
        'message' => 'License banned'
    ]);

    exit;
}
// ================= DOMAIN =================
if (
    !empty($license['domain_name']) &&
    $license['domain_name'] !== $domain
) {

    echo json_encode([
        'valid' => false,
        'message' => 'Wrong domain'
    ]);

    exit;
}

// ================= SUCCESS =================
echo json_encode([
    'valid' => true,
    'message' => 'License valid'
]);