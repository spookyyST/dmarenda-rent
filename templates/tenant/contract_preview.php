<section class="card">
    <h1>Предпросмотр договора</h1>
    <p class="muted">Полный текст договора с подстановкой данных</p>

    <div class="contract-preview">
        <?= $contract_html ?>
    </div>

    <form method="post" action="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/pay', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label class="checkbox"><input type="checkbox" name="consent_pd" value="1" required> Я согласен(на) на обработку персональных данных и ознакомлен(а) с <a href="<?= htmlspecialchars($privacy_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">политикой конфиденциальности</a></label>
        <label class="checkbox"><input type="checkbox" name="consent_contract" value="1" required> Я согласен(на) с условиями договора аренды</label>

        <div class="actions-row">
            <button type="submit">Перейти к оплате</button>
            <?php if (!empty($fake_payment_enabled)): ?>
                <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/cabinet', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Тестовая оплата из кабинета</a>
            <?php endif; ?>
            <?php if (!empty($contract['pdf_path'])): ?>
                <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/download/contract', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Скачать договор PDF</a>
            <?php endif; ?>
            <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/cabinet', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Личный кабинет</a>
            <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Назад</a>
        </div>
    </form>
</section>
