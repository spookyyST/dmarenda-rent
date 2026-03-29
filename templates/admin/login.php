<section class="card narrow">
    <h1>Вход администратора</h1>
    <form method="post" action="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/login', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label>
            Логин
            <input type="text" name="login" required autocomplete="username">
        </label>

        <label>
            Пароль
            <input type="password" name="password" required autocomplete="current-password">
        </label>

        <button type="submit">Войти</button>
    </form>
</section>
