<?php

declare(strict_types=1);

namespace Rent\Service;

use RuntimeException;

class FileStorageService
{
    public function __construct(private readonly array $config)
    {
    }

    public function saveUploadedFile(array $file, string $targetType): string
    {
        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Ошибка загрузки файла.');
        }

        $maxSize = (int) app_config($this->config, 'security.max_upload_mb', 10) * 1024 * 1024;
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxSize) {
            throw new RuntimeException('Недопустимый размер файла.');
        }

        $originalName = (string) ($file['name'] ?? 'file');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = app_config($this->config, 'security.allowed_extensions', []);
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Недопустимое расширение файла.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName)) {
            throw new RuntimeException('Временный файл не найден.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMime = app_config($this->config, 'security.allowed_mime_types', []);
        if (!in_array($mime, $allowedMime, true)) {
            throw new RuntimeException('Недопустимый MIME-тип файла.');
        }

        $targetDir = $this->resolveDirectory($targetType);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Не удалось создать директорию загрузок.');
        }

        $newName = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $newName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Не удалось сохранить файл.');
        }

        return '/uploads/' . $targetType . '/' . $newName;
    }

    public function absolutePath(string $relativePath): string
    {
        $storageRoot = (string) app_config($this->config, 'storage.uploads_root');
        // $relativePath like /uploads/passports/file.pdf — strip /uploads prefix
        $relative = preg_replace('#^/uploads#', '', $relativePath);
        return rtrim($storageRoot, '/') . '/' . ltrim((string) $relative, '/');
    }

    private function resolveDirectory(string $type): string
    {
        return match ($type) {
            'passports' => (string) app_config($this->config, 'storage.passports_dir'),
            'contracts' => (string) app_config($this->config, 'storage.contracts_dir'),
            'receipts' => (string) app_config($this->config, 'storage.receipts_dir'),
            default => throw new RuntimeException('Неизвестный тип хранилища.'),
        };
    }
}
