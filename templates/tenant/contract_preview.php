<?php
$now = app_now((string) ($app['timezone'] ?? 'Europe/Moscow'));
$monthMap = [
    1 => 'января',
    2 => 'февраля',
    3 => 'марта',
    4 => 'апреля',
    5 => 'мая',
    6 => 'июня',
    7 => 'июля',
    8 => 'августа',
    9 => 'сентября',
    10 => 'октября',
    11 => 'ноября',
    12 => 'декабря',
];
$contractDateDay = $now->format('d');
$contractDateMonth = $monthMap[(int) $now->format('n')] ?? '';
$contractDateYear = $now->format('Y');
$rentAmount = number_format((float) $invitation['rent_amount'], 2, '.', ' ');
$rentAmount = rtrim(rtrim($rentAmount, '0'), '.');
?>

<section class="card">
    <h1>Предпросмотр договора</h1>
    <p class="muted">Полный текст договора с подстановкой данных</p>

    <div class="contract-preview">
        <h2>ДОГОВОР АРЕНДЫ</h2>
        <p>г. <?= htmlspecialchars((string) $city, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> «<?= htmlspecialchars((string) $contractDateDay, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>» <?= htmlspecialchars((string) $contractDateMonth, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> <?= htmlspecialchars((string) $contractDateYear, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> г.</p>
        <p>Арендодатель: <?= htmlspecialchars((string) ($landlord['full_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (<?= htmlspecialchars((string) ($landlord['type'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>), ИНН <?= htmlspecialchars((string) ($landlord['inn'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, паспорт: серия <?= htmlspecialchars((string) ($landlord['passport_series'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> номер <?= htmlspecialchars((string) ($landlord['passport_number'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, выдан <?= htmlspecialchars((string) ($landlord['passport_issued_by'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> <?= htmlspecialchars((string) ($landlord['passport_date'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, зарегистрирован по адресу: <?= htmlspecialchars((string) ($landlord['registration_address'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, телефон: <?= htmlspecialchars((string) ($landlord['phone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, e-mail: <?= htmlspecialchars((string) ($landlord['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, именуемый в дальнейшем «Арендодатель».</p>
        <p>Арендатор: <?= htmlspecialchars((string) $tenant['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, паспорт: серия <?= htmlspecialchars((string) $tenant_profile['passport_series'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> номер <?= htmlspecialchars((string) $tenant_profile['passport_number'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, выдан <?= htmlspecialchars((string) $tenant_profile['passport_issued_by'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, дата выдачи <?= htmlspecialchars((string) $tenant_profile['passport_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, зарегистрирован по адресу: <?= htmlspecialchars((string) $tenant_profile['registration_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, телефон: <?= htmlspecialchars((string) $tenant['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, e-mail: <?= htmlspecialchars((string) $tenant['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, именуемый в дальнейшем «Арендатор».</p>

        <h2>1. ПРЕДМЕТ ДОГОВОРА</h2>
        <p>1.1. Арендодатель передает, а Арендатор принимает во временное владение и пользование объект: <?= htmlspecialchars((string) $invitation['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (далее – Объект).</p>
        <p>1.2. Объект принадлежит Арендодателю на праве собственности.</p>
        <p>1.3. Срок аренды устанавливается на 11 (одиннадцать) месяцев.</p>

        <h2>2. АРЕНДНАЯ ПЛАТА</h2>
        <p>2.1. Арендная плата составляет <?= htmlspecialchars((string) $rentAmount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> руб. в месяц.</p>
        <p>2.2. Первый платеж вносится в день подписания настоящего договора.</p>
        <p>2.3. Последующие платежи вносятся ежемесячно не позднее дня, предшествующего дате начала нового месяца аренды.</p>
        <p>2.4. Оплата производится по ссылке, которая автоматически направляется Арендатору на электронную почту после подписания договора (для первого платежа) и за 5 дней до даты очередного платежа (для последующих). Ссылка ведет на защищенную страницу оплаты через платежный сервис ЮKassa.</p>

        <h2>3. ПРАВА И ОБЯЗАННОСТИ СТОРОН</h2>
        <p>3.1. Арендодатель обязан:</p>
        <p>· передать Объект в состоянии, пригодном для использования;</p>
        <p>· не чинить препятствий в использовании Объекта;</p>
        <p>· производить капитальный ремонт за свой счет.</p>
        <p>3.2. Арендатор обязан:</p>
        <p>· своевременно вносить арендную плату;</p>
        <p>· использовать Объект по назначению;</p>
        <p>· содержать Объект в надлежащем состоянии;</p>
        <p>· не производить перепланировку без согласия Арендодателя.</p>

        <h2>4. ОТВЕТСТВЕННОСТЬ СТОРОН</h2>
        <p>4.1. За просрочку оплаты Арендатор уплачивает пени в размере 0,5% от суммы задолженности за каждый день просрочки.</p>
        <p>4.2. В случае порчи имущества Арендатор возмещает стоимость восстановления или полную рыночную стоимость.</p>

        <h2>5. РАСТОРЖЕНИЕ ДОГОВОРА</h2>
        <p>5.1. Договор может быть расторгнут досрочно по соглашению сторон.</p>
        <p>5.2. Арендодатель вправе отказаться от договора в одностороннем порядке при невнесении арендной платы более 15 дней.</p>
        <p>5.3. Арендатор вправе отказаться от договора, предупредив Арендодателя за 30 дней.</p>

        <h2>6. ФОРС-МАЖОР</h2>
        <p>Стороны освобождаются от ответственности за неисполнение обязательств, если это вызвано обстоятельствами непреодолимой силы.</p>

        <h2>7. ЗАКЛЮЧИТЕЛЬНЫЕ ПОЛОЖЕНИЯ</h2>
        <p>7.1. Договор составлен в двух экземплярах, по одному для каждой стороны.</p>
        <p>7.2. Все изменения и дополнения действительны в письменной форме.</p>

        <h2>8. РЕКВИЗИТЫ И ПОДПИСИ СТОРОН</h2>
        <p><strong>Арендодатель:</strong> <?= htmlspecialchars((string) ($landlord['full_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (<?= htmlspecialchars((string) ($landlord['type'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>), ИНН <?= htmlspecialchars((string) ($landlord['inn'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
        <p><strong>Арендатор:</strong> <?= htmlspecialchars((string) $tenant['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, паспорт <?= htmlspecialchars((string) $tenant_profile['passport_series'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> <?= htmlspecialchars((string) $tenant_profile['passport_number'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, тел. <?= htmlspecialchars((string) $tenant['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>, e-mail <?= htmlspecialchars((string) $tenant['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
    </div>

    <form method="post" action="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/pay', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label class="checkbox"><input type="checkbox" name="consent_pd" value="1" required> Я согласен(на) на обработку персональных данных и ознакомлен(а) с <a href="<?= htmlspecialchars($privacy_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">политикой конфиденциальности</a></label>
        <label class="checkbox"><input type="checkbox" name="consent_contract" value="1" required> Я согласен(на) с условиями договора аренды</label>

        <div class="actions-row">
            <button type="submit">Перейти к оплате</button>
            <?php if (!empty($contract['pdf_path'])): ?>
                <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/download/contract', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Скачать договор PDF</a>
            <?php endif; ?>
            <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/cabinet', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Личный кабинет</a>
            <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Назад</a>
        </div>
    </form>
</section>
