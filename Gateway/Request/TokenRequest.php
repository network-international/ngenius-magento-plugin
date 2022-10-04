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
        $this->clientFactory   = $clientFactory;
        $this->config          = $config;
        $this->logger          = $logger;
        $this->transferFactory = $transferFactory;
        $this->messageManager  = $messageManager;
    }

    /**
     * Gets Access Token
     *
     * @param int $storeId
     *
     * @return string
     * @throws CouldNotSaveException
     */
    public function getAccessToken($storeId = null)
    {
        $url = $this->config->getTokenRequestURL($storeId);
        $key = $this->config->getApiKey($storeId);

        $headers = array(
            "Authorization: Basic $key",
            "Content-Type:  application/vnd.ni-identity.v1+json"
        );

        $ch = curl_init();

        $curlConfig = array(
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
        );

        curl_setopt_array($ch, $curlConfig);
        $response = curl_exec($ch);
        $result   = json_decode($response);

        if (isset($result->access_token)) {
            return $result->access_token;
        } else {
            $message = 'Invalid Token';
            if (isset($result->errors[0]->message)) {
                $message = $result->errors[0]->message;
                $message .= '. This may be due to an error in the configured Environment, API URL or API Key';
            }
            throw new CouldNotSaveException(__($message));
        }
    }
}
