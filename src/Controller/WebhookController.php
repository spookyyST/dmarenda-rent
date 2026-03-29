<?php

declare(strict_types=1);

namespace Rent\Controller;

use Rent\Http\Request;
use Rent\Http\Response;
use Rent\Service\PaymentWorkflowService;
use Rent\Support\Logger;

class WebhookController
{
    public function __construct(
        private readonly PaymentWorkflowService $paymentWorkflowService,
        private readonly Logger $logger
    ) {
    }

    public function yookassa(Request $request): Response
    {
        $payload = $request->json();
        $requestIp = (string) $request->server('REMOTE_ADDR', $request->ip());

        if ($payload === null) {
            $this->logger->warning('Webhook payload is invalid JSON');
            return Response::json(['ok' => false, 'error' => 'invalid_json'], 400);
        }

        try {
            $this->paymentWorkflowService->handleWebhook($payload, $requestIp);
            return Response::json(['ok' => true], 200);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Webhook rejected', ['error' => $e->getMessage(), 'ip' => $requestIp]);
            return Response::json(['ok' => false, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logger->error('Webhook fatal error', ['error' => $e->getMessage()]);
            return Response::json(['ok' => false, 'error' => 'internal_error'], 500);
        }
    }
}
