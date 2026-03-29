<?php

declare(strict_types=1);

namespace Rent\Controller;

use Rent\Http\Response;
use Rent\Service\ContentService;
use Rent\Support\View;

class LegalController extends BaseController
{
    public function __construct(array $config, View $view, private readonly ContentService $contentService)
    {
        parent::__construct($config, $view);
    }

    public function privacy(): Response
    {
        return $this->render('legal/privacy.php', [
            'privacy_html' => $this->contentService->getPrivacyContent(),
        ], 'Политика конфиденциальности');
    }
}
