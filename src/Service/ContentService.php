<?php

declare(strict_types=1);

namespace Rent\Service;

class ContentService
{
    public function __construct(private readonly array $config)
    {
    }

    public function getPrivacyContent(): string
    {
        return $this->readOrDefault($this->privacyPath(), $this->defaultPrivacyContent());
    }

    public function savePrivacyContent(string $content): void
    {
        $this->write($this->privacyPath(), $content);
    }

    public function getContractTemplate(): string
    {
        return $this->readOrDefault($this->contractPath(), $this->defaultContractTemplate());
    }

    public function saveContractTemplate(string $content): void
    {
        $this->write($this->contractPath(), $content);
    }

    public function renderContractHtml(array $variables): string
    {
        $template = $this->getContractTemplate();
        $replacements = [];

        foreach ($variables as $key => $value) {
            $placeholder = '[' . $key . ']';
            if (is_scalar($value) || $value === null) {
                $replacements[$placeholder] = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }

        return strtr($template, $replacements);
    }

    private function readOrDefault(string $path, string $default): string
    {
        if (!is_file($path)) {
            $this->write($path, $default);
            return $default;
        }

        $content = (string) file_get_contents($path);
        return $content !== '' ? $content : $default;
    }

    private function write(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, $content);
    }

    private function privacyPath(): string
    {
        $contentDir = (string) app_config($this->config, 'storage.content_dir', __DIR__ . '/../../storage/content');
        return rtrim($contentDir, '/') . '/privacy.html';
    }

    private function contractPath(): string
    {
        $contentDir = (string) app_config($this->config, 'storage.content_dir', __DIR__ . '/../../storage/content');
        return rtrim($contentDir, '/') . '/contract_template.html';
    }

    private function defaultPrivacyContent(): string
    {
        return <<<HTML
<h1>Политика конфиденциальности</h1>
<p>Это шаблон страницы политики конфиденциальности для сервиса «ДМаренда».</p>
<p>Замените этот текст в админке перед запуском в продакшн.</p>

<h2>1. Общие положения</h2>
<p>Мы обрабатываем персональные данные арендаторов только в целях заключения и исполнения договора аренды.</p>

<h2>2. Состав данных</h2>
<p>ФИО, телефон, email, паспортные данные, адрес регистрации, сканы документов.</p>

<h2>3. Цели обработки</h2>
<p>Проверка данных арендатора, подготовка договора аренды, обработка платежей, отправка уведомлений.</p>

<h2>4. Сроки хранения</h2>
<p>Данные хранятся в течение срока действия договора и в течение периода, установленного законом.</p>

<h2>5. Контакты</h2>
<p>Email для запросов по данным: <a href="mailto:ids@drmhhh.com">ids@drmhhh.com</a>.</p>
HTML;
    }

    private function defaultContractTemplate(): string
    {
        return <<<HTML
<h1 style="text-align:center;">ДОГОВОР АРЕНДЫ</h1>
<p>г. [city] «[contract_date_day]» [contract_date_month] [contract_date_year] г.</p>
<p>Арендодатель: [landlord_full_name] ([landlord_type]), ИНН [landlord_inn], паспорт: серия [landlord_passport_series] номер [landlord_passport_number], выдан [landlord_passport_issued_by] [landlord_passport_date], зарегистрирован по адресу: [landlord_registration_address], телефон: [landlord_phone], e-mail: [landlord_email], именуемый в дальнейшем «Арендодатель».</p>
<p>Арендатор: [tenant_full_name], паспорт: серия [tenant_passport_series] номер [tenant_passport_number], выдан [tenant_passport_issued_by], дата выдачи [tenant_passport_date], зарегистрирован по адресу: [tenant_registration_address], телефон: [tenant_phone], e-mail: [tenant_email], именуемый в дальнейшем «Арендатор».</p>
<h2>1. ПРЕДМЕТ ДОГОВОРА</h2>
<p>1.1. Арендодатель передает, а Арендатор принимает во временное владение и пользование объект: [property_address] (далее – Объект).</p>
<p>1.2. Объект принадлежит Арендодателю на праве собственности.</p>
<p>1.3. Срок аренды устанавливается на 11 (одиннадцать) месяцев.</p>
<h2>2. АРЕНДНАЯ ПЛАТА</h2>
<p>2.1. Арендная плата составляет [rent_amount] руб. в месяц.</p>
<p>2.2. Первый платеж вносится в день подписания настоящего договора.</p>
<p>2.3. Последующие платежи вносятся ежемесячно не позднее дня, предшествующего дате начала нового месяца аренды.</p>
<p>2.4. Оплата производится по ссылке, которая автоматически направляется Арендатору на электронную почту после подписания договора (для первого платежа) и за 5 дней до даты очередного платежа (для последующих). Ссылка ведет на защищенную страницу оплаты через платежный сервис ЮKassa.</p>
<h2>3. ПРАВА И ОБЯЗАННОСТИ СТОРОН</h2>
<p>3.1. Арендодатель обязан:</p>
<p>· передать Объект в состоянии, пригодном для использования;</p>
<p>· не чинить препятствий в использовании Объекта;</p>
<p>· производить капитальный ремонт за свой счет.</p>
<p>3.2. Арендатор обязан:</p>
<p>· своевременно вносить арендную плату;</p>
<p>· использовать Объект по назначению;</p>
<p>· содержать Объект в надлежащем состоянии;</p>
<p>· не производить перепланировку без согласия Арендодателя.</p>
<h2>4. ОТВЕТСТВЕННОСТЬ СТОРОН</h2>
<p>4.1. За просрочку оплаты Арендатор уплачивает пени в размере 0,5% от суммы задолженности за каждый день просрочки.</p>
<p>4.2. В случае порчи имущества Арендатор возмещает стоимость восстановления или полную рыночную стоимость.</p>
<h2>5. РАСТОРЖЕНИЕ ДОГОВОРА</h2>
<p>5.1. Договор может быть расторгнут досрочно по соглашению сторон.</p>
<p>5.2. Арендодатель вправе отказаться от договора в одностороннем порядке при невнесении арендной платы более 15 дней.</p>
<p>5.3. Арендатор вправе отказаться от договора, предупредив Арендодателя за 30 дней.</p>
<h2>6. ФОРС-МАЖОР</h2>
<p>Стороны освобождаются от ответственности за неисполнение обязательств, если это вызвано обстоятельствами непреодолимой силы.</p>
<h2>7. ЗАКЛЮЧИТЕЛЬНЫЕ ПОЛОЖЕНИЯ</h2>
<p>7.1. Договор составлен в двух экземплярах, по одному для каждой стороны.</p>
<p>7.2. Все изменения и дополнения действительны в письменной форме.</p>
<h2>8. РЕКВИЗИТЫ И ПОДПИСИ СТОРОН</h2>
<p><strong>Арендодатель:</strong> [landlord_full_name] ([landlord_type]), ИНН [landlord_inn].</p>
<p><strong>Арендатор:</strong> [tenant_full_name], паспорт [tenant_passport_series] [tenant_passport_number], тел. [tenant_phone], e-mail [tenant_email].</p>
HTML;
    }
}

