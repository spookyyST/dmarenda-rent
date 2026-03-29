<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'ДМаренда',
        'base_url' => 'https://dmarenda.ru/rent',
        'base_path' => '/rent',
        'city' => 'Москва',
        'timezone' => 'Europe/Moscow',
        'privacy_path' => '/privacy',
    ],
    'db' => [
        'path' => __DIR__ . '/storage/db/rent.sqlite',
    ],
    'admin' => [
        'login' => 'admin',
        // default password is "password", change this hash before production launch.
        'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    ],
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'user' => 'ids@drmhhh.com',
        'pass' => 'CHANGE_ME',
        'encryption' => 'tls',
        'from_email' => 'ids@drmhhh.com',
        'from_name' => 'ДМаренда',
    ],
    'yookassa' => [
        'shop_id' => 'CHANGE_ME',
        'secret_key' => 'CHANGE_ME',
        'webhook_url' => 'https://dmarenda.ru/rent/webhooks/yookassa',
        'currency' => 'RUB',
        'test_mode' => false,
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
