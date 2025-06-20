<?php

namespace NetworkInternational\NGenius\Service;

use Exception;
use Laminas\Http\Request;
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Gateway\Request\TokenRequest;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;
use Psr\Log\LoggerInterface;

class NgeniusApiService
{
    private Config $config;
    private LoggerInterface $logger;
    private TokenRequest $tokenRequest;

    /**
     * @param Config $config
     * @param LoggerInterface $logger
     * @param TokenRequest $tokenRequest
     */
    public function __construct(Config $config, LoggerInterface $logger, TokenRequest $tokenRequest)
    {
        $this->config       = $config;
        $this->logger       = $logger;
        $this->tokenRequest = $tokenRequest;
    }

    /**
     * @throws Exception
     */
    public function createInvoice($order, array $invoiceData): array
    {
        $storeId       = $order->getStoreId();
        $token         = $this->tokenRequest->getAccessToken($storeId);
        $currencyCode  = $order->getOrderCurrencyCode();
        $paymentAction = $this->config->getPaymentAction($storeId);
        $url           = $this->config->getPayByLinkUrl($storeId, $paymentAction, $currencyCode);

        $httpTransfer = new NgeniusHTTPTransfer($url, $this->config->getHttpVersion($storeId));
        $httpTransfer->setInvoiceHeaders($token);
        $httpTransfer->setMethod(Request::METHOD_POST);
        $httpTransfer->setData($invoiceData);

        $response = json_decode(NgeniusHTTPCommon::placeRequest($httpTransfer), true);

        if (isset($response['errors'])) {
            $this->logger->error('N-Genius API Error: ' . $response["errors"][0]["message"]);
            throw new Exception($response['message']);
        }

        return $response;
    }

    public function isValidResponse(array $response): bool
    {
        return isset($response['orderReference']) && isset($response['transactionType']);
    }
}
