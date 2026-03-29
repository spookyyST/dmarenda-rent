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
$landlord = is_array($data['landlord'] ?? null) ? $data['landlord'] : [];
$rentAmount = number_format((float) ($data['rent_amount'] ?? 0), 2, '.', ' ');
$rentAmount = rtrim(rtrim($rentAmount, '0'), '.');
?>
<h1>ДОГОВОР АРЕНДЫ</h1>
<p>г. <?= htmlspecialchars((string) $data['city'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> «<?= htmlspecialchars((string) $data['contract_date_day'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>» <?= htmlspecialchars((string) $data['contract_date_month'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> <?= htmlspecialchars((string) $data['contract_date_year'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> г.</p>

<p>Арендодатель: <?= htmlspecialchars((string) ($landlord['full_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (<?= htmlspecialchars((string) ($landlord['type'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>), ИНН <?= htmlspecialchars((string) ($landlord['inn'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, паспорт: серия <?= htmlspecialchars((string) ($landlord['passport_series'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> номер <?= htmlspecialchars((string) ($landlord['passport_number'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, выдан <?= htmlspecialchars((string) ($landlord['passport_issued_by'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> <?= htmlspecialchars((string) ($landlord['passport_date'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, зарегистрирован по адресу: <?= htmlspecialchars((string) ($landlord['registration_address'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, телефон: <?= htmlspecialchars((string) ($landlord['phone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, e-mail: <?= htmlspecialchars((string) ($landlord['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, именуемый в дальнейшем «Арендодатель».</p>

<p>Арендатор: <?= htmlspecialchars((string) $data['tenant_full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, паспорт: серия <?= htmlspecialchars((string) $data['tenant_passport_series'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> номер <?= htmlspecialchars((string) $data['tenant_passport_number'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, выдан <?= htmlspecialchars((string) $data['tenant_passport_issued_by'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, дата выдачи <?= htmlspecialchars((string) $data['tenant_passport_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, зарегистрирован по адресу: <?= htmlspecialchars((string) $data['tenant_registration_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, телефон: <?= htmlspecialchars((string) $data['tenant_phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, e-mail: <?= htmlspecialchars((string) $data['tenant_email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, именуемый в дальнейшем «Арендатор».</p>

<h2>1. ПРЕДМЕТ ДОГОВОРА</h2>
<p class="section-item">1.1. Арендодатель передает, а Арендатор принимает во временное владение и пользование объект: <?= htmlspecialchars((string) $data['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (далее – Объект).</p>
<p class="section-item">1.2. Объект принадлежит Арендодателю на праве собственности.</p>
<p class="section-item">1.3. Срок аренды устанавливается на 11 (одиннадцать) месяцев.</p>

<h2>2. АРЕНДНАЯ ПЛАТА</h2>
<p class="section-item">2.1. Арендная плата составляет <?= htmlspecialchars((string) $rentAmount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> руб. в месяц.</p>
<p class="section-item">2.2. Первый платеж вносится в день подписания настоящего договора.</p>
<p class="section-item">2.3. Последующие платежи вносятся ежемесячно не позднее дня, предшествующего дате начала нового месяца аренды.</p>
<p class="section-item">2.4. Оплата производится по ссылке, которая автоматически направляется Арендатору на электронную почту после подписания договора (для первого платежа) и за 5 дней до даты очередного платежа (для последующих). Ссылка ведет на защищенную страницу оплаты через платежный сервис ЮKassa.</p>

<h2>3. ПРАВА И ОБЯЗАННОСТИ СТОРОН</h2>
<p class="section-item">3.1. Арендодатель обязан:</p>
<p class="bullet">· передать Объект в состоянии, пригодном для использования;</p>
<p class="bullet">· не чинить препятствий в использовании Объекта;</p>
<p class="bullet">· производить капитальный ремонт за свой счет.</p>
<p class="section-item">3.2. Арендатор обязан:</p>
<p class="bullet">· своевременно вносить арендную плату;</p>
<p class="bullet">· использовать Объект по назначению;</p>
<p class="bullet">· содержать Объект в надлежащем состоянии;</p>
<p class="bullet">· не производить перепланировку без согласия Арендодателя.</p>

<h2>4. ОТВЕТСТВЕННОСТЬ СТОРОН</h2>
<p class="section-item">4.1. За просрочку оплаты Арендатор уплачивает пени в размере 0,5% от суммы задолженности за каждый день просрочки.</p>
<p class="section-item">4.2. В случае порчи имущества Арендатор возмещает стоимость восстановления или полную рыночную стоимость.</p>

<h2>5. РАСТОРЖЕНИЕ ДОГОВОРА</h2>
<p class="section-item">5.1. Договор может быть расторгнут досрочно по соглашению сторон.</p>
<p class="section-item">5.2. Арендодатель вправе отказаться от договора в одностороннем порядке при невнесении арендной платы более 15 дней.</p>
<p class="section-item">5.3. Арендатор вправе отказаться от договора, предупредив Арендодателя за 30 дней.</p>

<h2>6. ФОРС-МАЖОР</h2>
<p class="section-item">Стороны освобождаются от ответственности за неисполнение обязательств, если это вызвано обстоятельствами непреодолимой силы.</p>

<h2>7. ЗАКЛЮЧИТЕЛЬНЫЕ ПОЛОЖЕНИЯ</h2>
<p class="section-item">7.1. Договор составлен в двух экземплярах, по одному для каждой стороны.</p>
<p class="section-item">7.2. Все изменения и дополнения действительны в письменной форме.</p>

<h2>8. РЕКВИЗИТЫ И ПОДПИСИ СТОРОН</h2>
<p><strong>Арендодатель:</strong></p>
<p><?= htmlspecialchars((string) ($landlord['full_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (<?= htmlspecialchars((string) ($landlord['type'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)</p>
<p>ИНН <?= htmlspecialchars((string) ($landlord['inn'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Паспорт: <?= htmlspecialchars((string) ($landlord['passport_series'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> <?= htmlspecialchars((string) ($landlord['passport_number'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Зарегистрирован: <?= htmlspecialchars((string) ($landlord['registration_address'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Тел.: <?= htmlspecialchars((string) ($landlord['phone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>E-mail: <?= htmlspecialchars((string) ($landlord['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>

<p><strong>Арендатор:</strong></p>
<p><?= htmlspecialchars((string) $data['tenant_full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Паспорт: <?= htmlspecialchars((string) $data['tenant_passport_series'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> <?= htmlspecialchars((string) $data['tenant_passport_number'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>Тел.: <?= htmlspecialchars((string) $data['tenant_phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<p>E-mail: <?= htmlspecialchars((string) $data['tenant_email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>

<div class="signatures">
    <p><strong>Подписи:</strong></p>
    <p>Арендодатель ____________________</p>
    <p>Арендатор ____________________</p>
</div>
</body>
</html>
