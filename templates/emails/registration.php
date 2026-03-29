<p>Здравствуйте, <?= htmlspecialchars((string) $tenant['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Регистрация в сервисе «<?= htmlspecialchars((string) $app['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>» завершена.</p>
<p>Объект: <?= htmlspecialchars((string) $invitation['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Вы можете перейти в личный кабинет для оплаты и контроля статуса: <a href="<?= htmlspecialchars((string) $cabinet_link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $cabinet_link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>.</p>
