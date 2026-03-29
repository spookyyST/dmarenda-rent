<p>Здравствуйте, <?= htmlspecialchars((string) $tenant['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Напоминаем о платеже по аренде объекта: <?= htmlspecialchars((string) $invitation['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Дата следующего платежа: <strong><?= htmlspecialchars((string) $next_payment_date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>.</p>
<p><a href="<?= htmlspecialchars((string) $pay_link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Перейти в личный кабинет для оплаты</a></p>
