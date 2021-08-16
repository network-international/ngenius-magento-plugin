<?php

namespace NetworkInternational\NGenius\Gateway\Request;

use Magento\Framework\HTTP\ZendClientFactory;
use NetworkInternational\NGenius\Gateway\Config\Config;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Payment\Model\Method\Logger;
use NetworkInternational\NGenius\Gateway\Http\TransferFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class TokenRequest
 */
class TokenRequest
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ZendClientFactory
     */
    protected $clientFactory;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * TokenRequest constructor.
     *
     * @param Config $config
     * @param Logger $logger
     * @param ZendClientFactory $clientFactory
     * @param TransferFactory $transferFactory
     * ManagerInterface $messageManager
     */
    public function __construct(
        Config $config,
        Logger $logger,
        ZendClientFactory $clientFactory,
        TransferFactory $transferFactory,
        ManagerInterface $messageManager
    ) {
        $this->clientFactory = $clientFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->transferFactory = $transferFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Gets Access Token
     *
     * @param int $storeId
     * @throws CouldNotSaveException
     * @return string
     */
    public function getAccessToken($storeId = null)
    {

        $request = [
            'request' => [
                'data' => http_build_query(['grant_type' => 'client_credentials']),
                'method' => \Zend_Http_Client::POST,
                'uri' => $this->config->getTokenRequestURL($storeId)
            ]
        ];
        $transferObject = $this->transferFactory->tokenBuild($request, $this->config->getApiKey($storeId));

        $result = [];
        $client = $this->clientFactory->create();
        $client->setConfig($transferObject->getClientConfig());
        $client->setMethod($transferObject->getMethod());
        $client->setRawData($transferObject->getBody());
        $client->setHeaders($transferObject->getHeaders());
        $client->setUri($transferObject->getUri());

        try {
            $response = $client->request();
            $result = json_decode($response->getBody());
            $log['response'] = $result;
            if (isset($result->access_token)) {
                return $result->access_token;
            } else {
                throw new CouldNotSaveException(__('Invalid Token.'));
            }
        } catch (\Zend_Http_Client_Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        } finally {
            //$this->logger->debug($log, null, true);
        }
    }
}
