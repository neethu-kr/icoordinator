<?php

namespace iCoordinator\Controller;

use iCoordinator\Service\ChargifyWebhookService;
use Slim\Http\Request;
use Slim\Http\Response;

class ChargifyController extends AbstractRestController
{

    public function processWebhookAction(Request $request, Response $response, $args)
    {
        $data       = $request->getParsedBody();
        $signature  = $request->getHeaderLine('X-Chargify-Webhook-Signature-Hmac-Sha-256');
        $body       = (string)$request->getBody();

        $webhookService = $this->getChargifyWebhookService();
        $webhookService->setData($data);

        if (!$webhookService->validateSignature($signature, $body)) {
            return $response->withStatus(403);
        }

        $result = $webhookService->processWebhook();

        return $response->withJson($result);
    }

    /**
     * @return ChargifyWebhookService
     */
    private function getChargifyWebhookService()
    {
        return $this->getContainer()->get('ChargifyWebhookService');
    }
}
