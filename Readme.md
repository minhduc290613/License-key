````md
# License Key System - Server & Client

## Overview

Hệ thống license gồm 2 phần:

- License Server: dùng để quản lý key
- Client Website: website napthe kiểm tra key từ server

License được lưu trên server riêng và website client sẽ gọi API để xác thực.

---

# 1. License Server

## Cấu trúc thư mục

```text
/license-server
│
├── env.php
├── license.php
└── .env
````

---

# 2. Database License Server

---

## Database

```sql
CREATE TABLE licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(255) NOT NULL,
    domain_name VARCHAR(255) DEFAULT NULL,
    status ENUM('active','banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

# 3. File .env (License Server)

## .env

```php
DB_HOST=localhost
DB_NAME=license_system
DB_USER=root
DB_PASS=password
```

---

# 4. File env.php

## env.php

```php
<?php

function loadEnv($file = '.env') {

    if (!file_exists($file)) {

        die('Không tìm thấy file .env');
    }

    $lines = file(
        $file,
        FILE_IGNORE_NEW_LINES |
        FILE_SKIP_EMPTY_LINES
    );

    foreach ($lines as $line) {

        if (
            strpos($line, '=') !== false &&
            strpos(trim($line), '#') !== 0
        ) {

            list($key, $value) =
                explode('=', $line, 2);

            $key = trim($key);

            $value = trim($value);

            $_ENV[$key] = $value;

            putenv("$key=$value");
        }
    }
}

loadEnv();
```

---

# 5. File verify.php (License API)

## verify.php

```php
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
```

---

# 6. Thêm License Key

## SQL

```sql
INSERT INTO licenses
(
    license_key,
    domain_name,
    status
)
VALUES
(
    'ABC-123-XYZ',
    'example.com',
    'active'
);
```

---

# 7. Client Website

## Cấu trúc

```text
/client
│
├── env.php
├── verify_license.php
└── .env
```

---

# 8. File .env (Client)

## .env

```php
LICENSE_KEY=ABC-123-XYZ
```
---

# 9. File license.php

## license.php

```php
<?php

require_once 'env.php';

// ================= LICENSE CONFIG =================
$verifyServer =
    'https://Yourdomain.com/server/verify.php';

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

    die('License invalid');
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
```

---

# 10. Sử dụng trên website

Thêm vào đầu file:

```php
require_once 'license.php';
```

Ví dụ:

```php
<?php

require_once 'license.php';

echo "Website hoạt động";
```

---

# 11. Cách hoạt động

## License hợp lệ

```json
{
    "valid": true,
    "message": "License valid"
}
```

## License sai

```json
{
    "valid": false,
    "message": "Invalid key"
}
```

## License bị khóa

```json
{
    "valid": false,
    "message": "License banned"
}
```

## Sai domain

```json
{
    "valid": false,
    "message": "License server offline"
}
```

---

# 12. Khóa License

## SQL

```sql
UPDATE licenses
SET STATUS = 'banned'
WHERE id = 1;
```

---

# 13. Mở khóa License

## SQL

```sql
UPDATE licenses
SET STATUS = 'active'
WHERE id = 1;
```

---

# 14. Bind Domain

## SQL

```sql
UPDATE licenses
SET domain_name = 'example.com'
WHERE id = 1;
```

---

# 15. Security Recommendations

* Dùng HTTPS cho license server
* Không public database
* Không lưu DB password trong code
* Dùng firewall
* Rate limit API
* Có thể thêm token hoặc hash bảo mật

---

# 16. Example API Request

```text
https://your-license-server.com/license.php?key=ABC-123-XYZ&domain=example.com
```

---

# 17. Example API Response

## Success

```json
{
    "valid": true,
    "message": "License valid"
}
```

## Failed

```json
{
    "valid": false,
    "message": "License banned"
}
```

```
```
