<?php

declare(strict_types=1);

namespace Rent\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Rent\Support\View;

class PdfService
{
    public function __construct(
        private readonly array $config,
        private readonly View $view,
        private readonly FileStorageService $storage
    ) {
    }

    public function generateContractPdf(array $payload): string
    {
        $html = $this->view->render('pdf/contract.php', ['data' => $payload]);
        return $this->renderAndStore($html, 'contracts', 'contract');
    }

    public function generateReceiptPdf(array $payload): string
    {
        $html = $this->view->render('pdf/receipt.php', ['data' => $payload]);
        return $this->renderAndStore($html, 'receipts', 'receipt');
    }

    private function renderAndStore(string $html, string $folder, string $prefix): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.pdf';
        $relativePath = '/uploads/' . $folder . '/' . $filename;
        $absolutePath = $this->storage->absolutePath($relativePath);

        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($absolutePath, $dompdf->output());

        return $relativePath;
    }
}
