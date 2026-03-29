<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'ДМаренда',
        'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8080',
        'base_path' => getenv('APP_BASE_PATH') ?: '',
        'city' => getenv('APP_CITY') ?: 'Москва',
        'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Moscow',
        'privacy_path' => '/privacy',
    ],
    'db' => [
        'path' => __DIR__ . '/storage/db/rent.sqlite',
    ],
    'admin' => [
        'login' => getenv('ADMIN_LOGIN') ?: 'admin',
        // default password is "password", change this hash before production launch.
        'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    ],
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
        'port' => (int) (getenv('SMTP_PORT') ?: 587),
        'user' => getenv('SMTP_USER') ?: 'ids@drmhhh.com',
        'pass' => getenv('SMTP_PASS') ?: 'CHANGE_ME',
        'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'ids@drmhhh.com',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'ДМаренда',
    ],
    'yookassa' => [
        'shop_id' => getenv('YOOKASSA_SHOP_ID') ?: 'CHANGE_ME',
        'secret_key' => getenv('YOOKASSA_SECRET_KEY') ?: 'CHANGE_ME',
        'webhook_url' => getenv('YOOKASSA_WEBHOOK_URL') ?: 'https://example.com/webhooks/yookassa',
        'currency' => getenv('YOOKASSA_CURRENCY') ?: 'RUB',
        'test_mode' => filter_var(getenv('YOOKASSA_TEST_MODE') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    ],
    'security' => [
        'max_upload_mb' => 10,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'application/pdf',
        ],
        'webhook_ip_allowlist' => [
            '185.71.76.0/27',
            '185.71.77.0/27',
            '77.75.153.0/25',
            '77.75.156.11',
            '77.75.156.35',
            '77.75.154.128/25',
            '2a02:5180::/32',
        ],
    ],
    'storage' => [
        'uploads_root' => __DIR__ . '/storage/files',
        'passports_dir' => __DIR__ . '/storage/files/passports',
        'contracts_dir' => __DIR__ . '/storage/files/contracts',
        'receipts_dir' => __DIR__ . '/storage/files/receipts',
        'logs_dir' => __DIR__ . '/storage/logs',
        'content_dir' => __DIR__ . '/storage/content',
    ],
    'landlord' => [
        'default_full_name' => 'Системный арендодатель',
        'default_email' => 'ids@drmhhh.com',
        'default_phone' => '+70000000000',
        'details' => [
            'full_name' => 'Ипатов Дмитрий Сергеевич',
            'type' => 'ИП',
            'inn' => '510705476261',
            'passport_series' => '4707',
            'passport_number' => '149840',
            'passport_issued_by' => 'Межрайонным отделом УФМС России по Мурманской области в городе Мончегорске',
            'passport_date' => '29.03.2008',
            'registration_address' => 'г. Санкт-Петербург, Приморский район, ул. Планерная, д. 87, к. 1, кв. 523',
            'phone' => '+7 965 780-00-13',
            'email' => 'ids@drmhhh.com',
        ],
    ],
];
