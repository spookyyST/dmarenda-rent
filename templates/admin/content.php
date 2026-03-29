<section class="card">
    <h1>Редактор контента</h1>
    <p class="muted">Здесь можно менять текст политики конфиденциальности и шаблон договора без правок кода.</p>

    <form method="post" action="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/content', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label for="privacy_content">Политика конфиденциальности (HTML)</label>
        <textarea id="privacy_content" name="privacy_content" rows="16" required><?= htmlspecialchars((string) $privacy_content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>

        <label for="contract_template">Шаблон договора (HTML + переменные)</label>
        <textarea id="contract_template" name="contract_template" rows="24" required><?= htmlspecialchars((string) $contract_template, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>

        <div class="card" style="background:#f9fbff;">
            <h2 style="margin-top:0;">Доступные переменные</h2>
            <p class="muted" style="margin-bottom:0;">[city], [contract_date_day], [contract_date_month], [contract_date_year], [tenant_full_name], [tenant_passport_series], [tenant_passport_number], [tenant_passport_issued_by], [tenant_passport_date], [tenant_registration_address], [tenant_phone], [tenant_email], [property_address], [rent_amount], [landlord_full_name], [landlord_type], [landlord_inn], [landlord_passport_series], [landlord_passport_number], [landlord_passport_issued_by], [landlord_passport_date], [landlord_registration_address], [landlord_phone], [landlord_email]</p>
        </div>

        <div class="actions-row">
            <button type="submit">Сохранить изменения</button>
            <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/privacy', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">Открыть страницу политики</a>
        </div>
    </form>
</section>
