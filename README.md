# ДМаренда (mini-app)

Независимый PHP + SQLite веб-сервис для автоматизации аренды недвижимости:
- админ создает приглашение;
- арендатор регистрируется по токен-ссылке, загружает документы и оплачивает через ЮKassa;
- webhook подтверждает оплату, генерирует PDF договора/чека и отправляет email;
- ежедневный cron отправляет напоминания за 5 дней до следующего платежа.

## 1. Стек

- PHP 8.1+
- SQLite (файл `storage/db/rent.sqlite`)
- Composer-пакеты:
  - `dompdf/dompdf`
  - `phpmailer/phpmailer`
- ЮKassa REST API (через cURL)

## 2. Структура

- `config.php` — все настройки
- `public/` — веб-точка входа (`index.php`, CSS, uploads)
- `src/` — контроллеры, сервисы, репозитории, миграции
- `templates/` — HTML/PDF/email шаблоны
- `database/schema.sql` — SQL-схема
- `bin/cron_reminders.php` — cron-скрипт напоминаний
- `storage/` — sqlite и логи

## 3. Установка

1. Установите зависимости:

```bash
cd rent
composer install --no-dev --optimize-autoloader
php -S localhost:8080 -t public public/router.php
```

2. Настройте `config.php`:
- `app.base_url` = `https://dmarenda.ru/rent`
- `app.base_path` = `/rent`
- `smtp.*` (отправитель `ids@drmhhh.com`)
- `yookassa.shop_id`, `yookassa.secret_key`, `yookassa.webhook_url`
- `testing.fake_payment_enabled=true` (только для локального теста кнопкой, на проде `false`)
- `admin.login`, `admin.password_hash`

3. Права на каталоги:

```bash
chmod -R 775 storage public/uploads
```

4. Настройте веб-сервер так, чтобы URL `/rent/*` указывал на `rent/public`.

## 4. Веб-сервер

### Nginx (пример)

```nginx
location /rent/ {
    alias /var/www/site/rent/public/;
    index index.php;
    try_files $uri $uri/ /rent/index.php?$query_string;
}

location ~ ^/rent/(.+\.php)$ {
    alias /var/www/site/rent/public/$1;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME /var/www/site/rent/public/$1;
}
```

### Apache

- включите `mod_rewrite`;
- используйте `DocumentRoot` на `rent/public` для поддомена/алиаса,
  либо alias `/rent` на `rent/public`;
- внутри `public/.htaccess` уже есть rewrite-правила.

## 5. Первый запуск

База и таблицы создаются автоматически при первом HTTP-запросе или запуске cron.

Откройте:
- `https://dmarenda.ru/rent/admin/login`

Пароль — тот, для которого вы сгенерировали `password_hash` в `config.php`.
Для локального старта из шаблона `config.php`: логин `admin`, пароль `password`.

## 6. ЮKassa

1. В личном кабинете ЮKassa добавьте webhook URL:
- `https://dmarenda.ru/rent/webhooks/yookassa`

2. Подпишитесь минимум на события:
- `payment.succeeded`
- `payment.canceled` (опционально)

3. Проверьте HTTPS и TLS 1.2+.

4. Безопасность webhook в коде:
- allowlist IP ЮKassa (`security.webhook_ip_allowlist`)
- server-side проверка статуса платежа через API
- идемпотентность через таблицу `webhook_events`

## 7. SMTP

Заполните в `config.php`:
- `smtp.host`
- `smtp.port`
- `smtp.user`
- `smtp.pass`
- `smtp.encryption` (`tls` или `ssl`)
- `smtp.from_email` = `ids@drmhhh.com`

Письма отправляются:
- после регистрации;
- после успешной оплаты (договор + чек во вложениях);
- напоминание за 5 дней.

## 8. Cron

Добавьте задачу (ежедневно в 10:00 Europe/Moscow):

```cron
0 10 * * * /usr/bin/php /var/www/site/rent/bin/cron_reminders.php >> /var/www/site/rent/storage/logs/cron.log 2>&1
```

Cron проверяет `next_payment_date` и отправляет 1 письмо (без дублей) через `notifications`.

## 9. Загрузка файлов

Файлы хранятся в:
- `public/uploads/passports`
- `public/uploads/contracts`
- `public/uploads/receipts`

Исполняемые файлы в uploads запрещены (`public/uploads/.htaccess`).

## 10. Маршруты

- `GET/POST /rent/admin/login`
- `GET /rent/admin/logout`
- `GET /rent/admin/invitations`
- `GET/POST /rent/admin/invitations/new`
- `GET /rent/admin/contracts`
- `GET /rent/admin/payments`
- `GET /rent/admin/tenant/{id}`
- `GET /rent/admin/download/contract/{id}`
- `GET /rent/admin/download/receipt/{paymentId}`
- `GET/POST /rent/i/{token}`
- `GET /rent/i/{token}/contract`
- `GET /rent/i/{token}/cabinet`
- `GET/POST /rent/i/{token}/pay`
- `GET /rent/i/{token}/pay/fake-confirm/{paymentId}` (только при `yookassa.test_mode=true`)
- `POST /rent/i/{token}/pay/fake` (кнопка тестовой оплаты, только при `testing.fake_payment_enabled=true` или `yookassa.test_mode=true`)
- `GET /rent/i/{token}/pay/return`
- `GET /rent/i/{token}/download/contract`
- `GET /rent/i/{token}/download/receipt/{paymentId}`
- `POST /rent/webhooks/yookassa`
- `GET /rent/privacy`

## 11. HTTPS и безопасность

- Используйте только HTTPS на production.
- Смените default hash администратора.
- Ограничьте доступ к `config.php` на уровне веб-сервера.
- Регулярно проверяйте логи `storage/logs/app.log`.
