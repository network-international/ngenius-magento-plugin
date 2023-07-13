<?php

namespace NetworkInternational\NGenius\Gateway\Request;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Model\Method\Logger;
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Gateway\Http\TransferFactory;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;

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
     * @param TransferFactory $transferFactory
     * ManagerInterface $messageManager
     */
    public function __construct(
        Config $config,
        Logger $logger,
        TransferFactory $transferFactory,
        ManagerInterface $messageManager
    ) {
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

        $ngeniusHttpTransfer = new NgeniusHTTPTransfer($url, $this->config->getHttpVersion($storeId));
        $ngeniusHttpTransfer->setTokenHeaders($key);
        $ngeniusHttpTransfer->setMethod('POST');

        $response = NgeniusHTTPCommon::placeRequest($ngeniusHttpTransfer);
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
