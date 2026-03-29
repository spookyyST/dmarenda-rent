<p>Здравствуйте.</p>
<p>Для вас создано приглашение в сервисе «<?= htmlspecialchars((string) $app['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>».</p>
<p>Адрес объекта: <?= htmlspecialchars((string) $invitation['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Сумма аренды: <?= htmlspecialchars(number_format((float) $invitation['rent_amount'], 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> RUB.</p>
<p>Дата начала аренды: <?= htmlspecialchars((string) $invitation['start_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Ссылка для регистрации: <a href="<?= htmlspecialchars((string) $register_link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $register_link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></p>
