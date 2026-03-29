<p>Здравствуйте, <?= htmlspecialchars((string) $tenant['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Оплата аренды подтверждена.</p>
<p>Адрес объекта: <?= htmlspecialchars((string) $invitation['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Сумма: <?= htmlspecialchars(number_format((float) $payment['amount'], 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> RUB.</p>
<p>Вложения: договор аренды и чек PDF.</p>
