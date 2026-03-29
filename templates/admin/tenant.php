<section class="card">
    <h1>Данные арендатора</h1>

    <div class="details-grid">
        <div><strong>ФИО:</strong> <?= htmlspecialchars((string) $tenant['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div><strong>Email:</strong> <?= htmlspecialchars((string) $tenant['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div><strong>Телефон:</strong> <?= htmlspecialchars((string) $tenant['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div><strong>Серия паспорта:</strong> <?= htmlspecialchars((string) $tenant_profile['passport_series'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div><strong>Номер паспорта:</strong> <?= htmlspecialchars((string) $tenant_profile['passport_number'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div><strong>Кем выдан:</strong> <?= htmlspecialchars((string) $tenant_profile['passport_issued_by'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div><strong>Дата выдачи:</strong> <?= htmlspecialchars((string) $tenant_profile['passport_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div><strong>Адрес регистрации:</strong> <?= htmlspecialchars((string) $tenant_profile['registration_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    </div>

    <h2>Сканы паспорта</h2>
    <div class="scan-grid">
        <figure>
            <figcaption>Разворот</figcaption>
            <a href="<?= htmlspecialchars(rtrim($base_path, '/') . $tenant_profile['passport_scan_main'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">Открыть файл</a>
        </figure>
        <figure>
            <figcaption>Прописка</figcaption>
            <a href="<?= htmlspecialchars(rtrim($base_path, '/') . $tenant_profile['passport_scan_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">Открыть файл</a>
        </figure>
    </div>
</section>
