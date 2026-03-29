<?php

declare(strict_types=1);

namespace Rent\Controller;

use Rent\Http\Response;
use Rent\Support\View;

class LegalController extends BaseController
{
    public function __construct(array $config, View $view)
    {
        parent::__construct($config, $view);
    }

    public function privacy(): Response
    {
        return $this->render('legal/privacy.php', [], 'Политика конфиденциальности');
    }
}
