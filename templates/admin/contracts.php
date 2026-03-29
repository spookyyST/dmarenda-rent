<section class="card">
    <h1>Договоры</h1>

    <div class="filters">
        <?php foreach (['all' => 'Все', 'new' => 'Новые', 'registered' => 'Зарегистрированы', 'paid' => 'Оплачены'] as $key => $label): ?>
            <?php
            $url = rtrim($base_path, '/') . '/admin/contracts';
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
                <th>Арендатор</th>
                <th>Email</th>
                <th>Объект</th>
                <th>Сумма</th>
                <th>Дата старта</th>
                <th>Статус</th>
                <th>PDF</th>
                <th>Профиль</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($contracts === []): ?>
                <tr>
                    <td colspan="9">Нет договоров</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($contracts as $contract): ?>
                <tr>
                    <td><?= (int) $contract['id'] ?></td>
                    <td><?= htmlspecialchars((string) $contract['tenant_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $contract['tenant_email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $contract['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(number_format((float) $contract['rent_amount'], 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $contract['start_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><span class="status status-<?= htmlspecialchars((string) $contract['invitation_status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $contract['invitation_status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></td>
                    <td>
                        <?php if (!empty($contract['pdf_path'])): ?>
                            <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/download/contract/' . $contract['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Скачать</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/tenant/' . $contract['tenant_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Открыть</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
