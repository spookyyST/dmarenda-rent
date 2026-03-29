<?php
$o = $old ?? [];
$v = fn(string $key) => htmlspecialchars((string) ($o[$key] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="card narrow">
    <h1>Регистрация арендатора</h1>
    <div class="invite-meta">
        <div class="invite-meta-item">📍 <?= htmlspecialchars((string) $invitation['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div class="invite-meta-item">💰 <?= htmlspecialchars(number_format((float) $invitation['rent_amount'], 0, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> ₽/мес</div>
    </div>

    <?php if ($is_registered): ?>
        <div class="alert alert-success">✅ Регистрация завершена. Перейдите к договору и оплате.</div>
        <div class="actions-row">
            <a class="button" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/cabinet', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Личный кабинет</a>
            <a class="button" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/contract', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Просмотреть договор</a>
            <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/pay', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Оплатить</a>
        </div>
    <?php else: ?>
        <form method="post" action="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" enctype="multipart/form-data" class="form-grid" id="reg-form" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

            <div class="form-section-title">Личные данные</div>

            <div class="field">
                <label for="full_name">ФИО <span class="req">*</span></label>
                <input type="text" id="full_name" name="full_name"
                       value="<?= $v('full_name') ?>"
                       placeholder="Иванов Иван Иванович"
                       autocomplete="name" required>
                <span class="field-hint">Как в паспорте</span>
            </div>

            <div class="field">
                <label for="phone">Телефон <span class="req">*</span></label>
                <input type="tel" id="phone" name="phone"
                       value="<?= $v('phone') ?>"
                       placeholder="+7 (900) 000-00-00"
                       autocomplete="tel" required>
            </div>

            <div class="field">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars((string) $invitation['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                       autocomplete="email" readonly>
                <span class="field-hint">Email задан приглашением</span>
            </div>

            <div class="form-section-title">Паспортные данные</div>

            <div class="field-row">
                <div class="field">
                    <label for="passport_series">Серия <span class="req">*</span></label>
                    <input type="text" id="passport_series" name="passport_series"
                           value="<?= $v('passport_series') ?>"
                           placeholder="4507"
                           maxlength="4"
                           pattern="\d{4}"
                           inputmode="numeric"
                           required>
                </div>
                <div class="field">
                    <label for="passport_number">Номер <span class="req">*</span></label>
                    <input type="text" id="passport_number" name="passport_number"
                           value="<?= $v('passport_number') ?>"
                           placeholder="123456"
                           maxlength="6"
                           pattern="\d{6}"
                           inputmode="numeric"
                           required>
                </div>
            </div>

            <div class="field">
                <label for="passport_issued_by">Кем выдан <span class="req">*</span></label>
                <input type="text" id="passport_issued_by" name="passport_issued_by"
                       value="<?= $v('passport_issued_by') ?>"
                       placeholder="ОУФМС России по г. Москве"
                       required>
            </div>

            <div class="field">
                <label for="passport_date">Дата выдачи <span class="req">*</span></label>
                <input type="date" id="passport_date" name="passport_date"
                       value="<?= $v('passport_date') ?>"
                       max="<?= date('Y-m-d') ?>"
                       required>
            </div>

            <div class="field">
                <label for="registration_address">Адрес регистрации <span class="req">*</span></label>
                <input type="text" id="registration_address" name="registration_address"
                       value="<?= $v('registration_address') ?>"
                       placeholder="г. Москва, ул. Примерная, д. 1, кв. 1"
                       required>
            </div>

            <div class="form-section-title">Сканы паспорта</div>

            <div class="field">
                <label for="passport_scan_main">Разворот с фото <span class="req">*</span></label>
                <div class="file-drop" id="drop-main">
                    <input type="file" id="passport_scan_main" name="passport_scan_main"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="file-drop-label">
                        <span class="file-icon">📎</span>
                        <span class="file-text">Выберите файл или перетащите сюда</span>
                        <span class="file-hint">JPG, PNG или PDF, до 10 МБ</span>
                    </div>
                </div>
            </div>

            <div class="field">
                <label for="passport_scan_address">Страница с пропиской <span class="req">*</span></label>
                <div class="file-drop" id="drop-address">
                    <input type="file" id="passport_scan_address" name="passport_scan_address"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="file-drop-label">
                        <span class="file-icon">📎</span>
                        <span class="file-text">Выберите файл или перетащите сюда</span>
                        <span class="file-hint">JPG, PNG или PDF, до 10 МБ</span>
                    </div>
                </div>
            </div>

            <div class="form-section-title">Согласия</div>

            <label class="checkbox">
                <input type="checkbox" name="consent_pd" value="1" required>
                <span>Согласен(на) на обработку персональных данных и ознакомлен(а) с <a href="<?= htmlspecialchars($privacy_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">политикой конфиденциальности</a></span>
            </label>

            <label class="checkbox">
                <input type="checkbox" name="consent_contract" value="1" required>
                <span>Согласен(на) с условиями договора аренды</span>
            </label>

            <button type="submit" class="button-submit" id="submit-btn">
                Завершить регистрацию
            </button>
        </form>
    <?php endif; ?>
</section>

<script>
// Маска телефона
const phoneInput = document.getElementById('phone');
if (phoneInput) {
    phoneInput.addEventListener('input', function () {
        let val = this.value.replace(/\D/g, '');
        if (val.startsWith('8')) val = '7' + val.slice(1);
        if (!val.startsWith('7') && val.length > 0) val = '7' + val;
        val = val.slice(0, 11);
        let res = '';
        if (val.length > 0)  res = '+7';
        if (val.length > 1)  res += ' (' + val.slice(1, 4);
        if (val.length >= 4) res += ')';
        if (val.length > 4)  res += ' ' + val.slice(4, 7);
        if (val.length > 7)  res += '-' + val.slice(7, 9);
        if (val.length > 9)  res += '-' + val.slice(9, 11);
        this.value = res;
    });
}

// Только цифры — серия и номер паспорта
['passport_series', 'passport_number'].forEach(function(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });
});

// Автоперевод серии в цифры и ограничение длины
const series = document.getElementById('passport_series');
const number = document.getElementById('passport_number');
if (series) {
    series.addEventListener('input', function() {
        if (this.value.length === 4) number && number.focus();
    });
}

// Красивый файловый input
document.querySelectorAll('.file-drop').forEach(function(drop) {
    const input = drop.querySelector('input[type=file]');
    const text  = drop.querySelector('.file-text');
    if (!input || !text) return;

    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            text.textContent = '✅ ' + this.files[0].name;
            drop.classList.add('has-file');
        }
    });

    drop.addEventListener('dragover', function(e) {
        e.preventDefault();
        drop.classList.add('drag-over');
    });
    drop.addEventListener('dragleave', function() {
        drop.classList.remove('drag-over');
    });
    drop.addEventListener('drop', function(e) {
        e.preventDefault();
        drop.classList.remove('drag-over');
        if (e.dataTransfer.files[0]) {
            input.files = e.dataTransfer.files;
            text.textContent = '✅ ' + e.dataTransfer.files[0].name;
            drop.classList.add('has-file');
        }
    });
});

// Блокируем кнопку при отправке
const form = document.getElementById('reg-form');
const btn  = document.getElementById('submit-btn');
if (form && btn) {
    form.addEventListener('submit', function() {
        btn.disabled = true;
        btn.textContent = 'Отправка...';
    });
}
</script>
