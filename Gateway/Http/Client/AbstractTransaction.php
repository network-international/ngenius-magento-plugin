<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use NetworkInternational\NGenius\Model\CoreFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Message\ManagerInterface;

/*
 * Class AbstractTransaction
 */

abstract class AbstractTransaction implements ClientInterface
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ZendClientFactory
     */
    protected $clientFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CoreFactory
     */
    protected $coreFactory;

    /**
     * @var \NetworkInternational\NGenius\Setup\InstallData::STATUS
     */
    protected $orderStatus;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * AbstractTransaction constructor.
     *
     * @param ZendClientFactory $clientFactory
     * @param Logger $logger
     * @param Session $checkoutSession
     * @param CoreFactory $coreFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        ZendClientFactory $clientFactory,
        Logger $logger,
        Session $checkoutSession,
        CoreFactory $coreFactory,
        ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->clientFactory = $clientFactory;
        $this->checkoutSession = $checkoutSession;
        $this->coreFactory = $coreFactory;
        $this->orderStatus = \NetworkInternational\NGenius\Setup\InstallData::STATUS;
        $this->messageManager = $messageManager;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {

        $this->checkoutSession->unsPaymentURL();

        $data = $this->preProcess($transferObject->getBody());
        $log = [
            'request' => $data,
            'request_uri' => $transferObject->getUri()
        ];
        $result = [];
        $client = $this->clientFactory->create();
        $client->setConfig($transferObject->getClientConfig());
        $client->setMethod($transferObject->getMethod());
        $client->setRawData($data);
        $client->setHeaders($transferObject->getHeaders());
        $client->setUri($transferObject->getUri());

        try {
            $response = $client->request();
            if ($response->isSuccessful()) {
                $result = $response->getRawBody();
                $log['response'] = $result;
                return $this->postProcess($result);
            } else {
                $log['response'] = $response->getRawBody();
                $errCode = $response->getStatus();
                if ((int) $errCode == 409) {
                    $error = 'Failed! Please do the transaction after payment settlement.';
                } else {
                    $error = 'Failed! #' . $errCode . ': ' . $response->getMessage();
                }
                $this->messageManager->addError(__($error));
            }
        } catch (\Zend_Http_Client_Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        } finally {
            $this->logger->debug($log);
        }
    }

    /**
     * Processing of API request body
     *
     * @param array $data
     * @return string
     */
    abstract protected function preProcess(array $data);

    /**
     * Processing of API response
     *
     * @param array $response
     * @return null|array
     */
    abstract protected function postProcess($response);
}
