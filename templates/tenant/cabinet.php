<section class="card">
    <h1>Личный кабинет арендатора</h1>
    <p class="muted">Арендатор: <?= htmlspecialchars((string) $tenant['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <p class="muted">Объект: <?= htmlspecialchars((string) $invitation['property_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>

    <div class="details-grid">
        <div><strong>Сумма аренды:</strong> <?= htmlspecialchars(number_format((float) $invitation['rent_amount'], 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> RUB</div>
        <div><strong>Дата начала аренды:</strong> <?= htmlspecialchars((string) $invitation['start_date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div><strong>Статус приглашения:</strong> <span class="status status-<?= htmlspecialchars((string) $invitation['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $invitation['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></div>
        <div><strong>Дата следующего платежа:</strong> <?= htmlspecialchars((string) ($latest_payment['next_payment_date'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    </div>

    <div class="actions-row">
        <a class="button" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/pay', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Оплатить</a>
        <?php if (!empty($fake_payment_enabled)): ?>
            <form method="post" action="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/pay/fake', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" style="display:inline-flex;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <button type="submit" class="button secondary">Тестовая оплата</button>
            </form>
        <?php endif; ?>
        <?php if (!empty($contract['pdf_path'])): ?>
            <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/download/contract', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Скачать договор PDF</a>
        <?php endif; ?>
    </div>
</section>

<section class="card">
    <h2>История платежей</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Payment ID</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Оплачен</th>
                <th>Следующая дата</th>
                <th>Чек</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($payment_history === []): ?>
                <tr>
                    <td colspan="7">Платежей пока нет.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($payment_history as $payment): ?>
                <tr>
                    <td><?= (int) $payment['id'] ?></td>
                    <td><?= htmlspecialchars((string) $payment['payment_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(number_format((float) $payment['amount'], 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> RUB</td>
                    <td><span class="status status-<?= htmlspecialchars((string) $payment['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $payment['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars((string) ($payment['paid_at'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($payment['next_payment_date'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td>
                        <?php if (!empty($payment['receipt_pdf_path'])): ?>
                            <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/download/receipt/' . $payment['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Скачать чек</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
