<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; color: #000; }
        h1 { font-size: 18px; margin: 0 0 12px; text-align: center; }
        h2 { font-size: 13px; margin: 14px 0 8px; }
        p { margin: 0 0 8px; }
        .section-item { margin: 0 0 6px; }
        .bullet { margin: 0 0 4px 16px; }
        .signatures { margin-top: 18px; }
    </style>
</head>
<body>
<?php
$contractHtml = (string) ($data['contract_html'] ?? '');
?>
<?= $contractHtml ?>
</body>
</html>
