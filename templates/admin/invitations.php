<section class="card">
    <div class="section-head">
        <h1>Приглашения</h1>
        <a class="button" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/invitations/new', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Новое приглашение</a>
    </div>

    <div class="filters">
        <?php foreach (['all' => 'Все', 'new' => 'Новые', 'registered' => 'Зарегистрированы', 'paid' => 'Оплачены'] as $key => $label): ?>
            <?php
            $url = rtrim($base_path, '/') . '/admin/invitations';
            if ($key !== 'all') {
                $url .= '?status=' . urlencode($key);
            }
            $active = ($key === 'all' && !$selected_status) || ($selected_status === $key);
            ?>
            <a class="filter-link<?= $active ? ' active' : '' ?>" href="<?= htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Объект</th>
                <th>Сумма</th>
                <th>Дата старта</th>
                <th>Статус</th>
                <th>Ссылка</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($invitations === []): ?>
                <tr>
                    <td colspan="8">Нет данных</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($invitations as $invitation): ?>
                <?php $inviteUrl = rtrim((string) $base_url, '/') . '/i/' . $invitation['token']; ?>
                <tr>
                    <td><?= (int) $invitation['id'] ?></td>
                    <td><?= htmlspecialchars((string) $invitation['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($invitation['phone'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $invitation['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(number_format((float) $invitation['rent_amount'], 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $invitation['start_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><span class="status status-<?= htmlspecialchars((string) $invitation['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $invitation['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></td>
                    <td><a href="<?= htmlspecialchars($inviteUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">Открыть</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
