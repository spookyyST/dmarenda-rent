<section class="card">
    <h1>Создать приглашение</h1>

    <form method="post" action="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/invitations/new', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label>
            Email арендатора
            <input type="email" name="email" required>
        </label>

        <label>
            Телефон арендатора (опционально)
            <input type="text" name="phone" placeholder="+7...">
        </label>

        <label>
            Адрес объекта
            <input type="text" name="property_address" required>
        </label>

        <label>
            Сумма аренды (RUB)
            <input type="number" min="1" step="0.01" name="rent_amount" required>
        </label>

        <label>
            Дата начала аренды
            <input type="date" name="start_date" required>
        </label>

        <div class="actions-row">
            <button type="submit">Создать приглашение</button>
            <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/invitations', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Отмена</a>
        </div>
    </form>
</section>
