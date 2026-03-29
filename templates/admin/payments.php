<section class="card">
    <h1>Платежи</h1>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Payment ID (ЮKassa)</th>
                <th>Арендатор</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Следующий платеж</th>
                <th>Дата оплаты</th>
                <th>Чек PDF</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($payments === []): ?>
                <tr class="table-empty">
                    <td colspan="8">Нет платежей</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td data-label="ID"><?= (int) $payment['id'] ?></td>
                    <td data-label="Payment ID"><?= htmlspecialchars((string) $payment['payment_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td data-label="Арендатор"><?= htmlspecialchars((string) $payment['tenant_name'] . ' (' . $payment['tenant_email'] . ')', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td data-label="Сумма"><?= htmlspecialchars(number_format((float) $payment['amount'], 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td data-label="Статус"><span class="status status-<?= htmlspecialchars((string) $payment['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars((string) $payment['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></td>
                    <td data-label="Следующий платеж"><?= htmlspecialchars((string) ($payment['next_payment_date'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td data-label="Дата оплаты"><?= htmlspecialchars((string) ($payment['paid_at'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td data-label="Чек PDF">
                        <?php if (!empty($payment['receipt_pdf_path'])): ?>
                            <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/download/receipt/' . $payment['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Скачать</a>
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
