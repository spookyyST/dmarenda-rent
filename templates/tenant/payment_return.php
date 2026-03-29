<section class="card narrow">
    <h1>Статус оплаты</h1>
    <p>Если платеж успешно завершен, договор и чек будут отправлены на email.</p>

    <?php if ($latest_payment !== null && (string) $latest_payment['status'] === 'succeeded'): ?>
        <div class="alert alert-success">
            Последний подтвержденный платеж: <?= htmlspecialchars((string) $latest_payment['payment_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>,
            дата: <?= htmlspecialchars((string) ($latest_payment['paid_at'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.
        </div>
    <?php elseif ($latest_payment !== null): ?>
        <div class="alert">
            Текущий статус платежа: <?= htmlspecialchars((string) ($latest_payment['status'] ?? 'pending'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.
            Если статус не изменится в течение нескольких минут, повторите оплату.
        </div>
    <?php else: ?>
        <div class="alert">Платеж еще не подтвержден webhook-ом. Подождите 1-2 минуты и обновите страницу.</div>
    <?php endif; ?>

    <div class="actions-row">
        <a class="button" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/cabinet', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Личный кабинет</a>
        <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/contract', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">К договору</a>
        <a class="button secondary" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/i/' . $invitation['token'] . '/pay', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Оплатить снова</a>
    </div>
</section>
