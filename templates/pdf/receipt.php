<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; }
        h1 { font-size: 18px; margin: 0 0 10px; }
        p { margin: 0 0 8px; }
    </style>
</head>
<body>
<h1>Чек об оплате аренды</h1>
<p>Сервис: <?= htmlspecialchars((string) $data['service_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Город: <?= htmlspecialchars((string) $data['city'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Дата оплаты: <?= htmlspecialchars((string) $data['paid_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Payment ID: <?= htmlspecialchars((string) $data['payment_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Арендатор: <?= htmlspecialchars((string) $data['tenant']['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (<?= htmlspecialchars((string) $data['tenant']['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)</p>
<p>Объект: <?= htmlspecialchars((string) $data['invitation']['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Сумма: <?= htmlspecialchars(number_format((float) $data['amount'], 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> RUB</p>
</body>
</html>
