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