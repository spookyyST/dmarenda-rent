<?php

declare(strict_types=1);

namespace Rent\Support;

class View
{
    public function __construct(private readonly string $templatesDir)
    {
    }

    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->templatesDir . '/' . ltrim($template, '/');
        if (!is_file($templatePath)) {
            throw new \RuntimeException('Template not found: ' . $templatePath);
        }

        extract($data, EXTR_OVERWRITE);

        ob_start();
        require $templatePath;
        return (string) ob_get_clean();
    }
}
