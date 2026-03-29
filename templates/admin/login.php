<section class="card narrow">
    <div class="welcome-brand">
        <span class="welcome-brand-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 11.2 12 4l9 7.2v8.3a1.5 1.5 0 0 1-1.5 1.5h-4.8v-6h-5.4v6H4.5A1.5 1.5 0 0 1 3 19.5v-8.3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            </svg>
        </span>
        <div>
            <div class="welcome-brand-title">ДМаренда</div>
            <div class="welcome-brand-subtitle">Сервис аренды недвижимости</div>
        </div>
    </div>

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
