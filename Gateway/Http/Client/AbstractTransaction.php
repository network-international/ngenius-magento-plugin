<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use NetworkInternational\NGenius\Model\CoreFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;

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
     * @var \NetworkInternational\NGenius\Setup\InstallData::getStatuses()
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
        $this->orderStatus = \NetworkInternational\NGenius\Setup\InstallData::getStatuses();
        $this->messageManager = $messageManager;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest($request)
    {
        if (is_array($request) && $request['request']['method'] == "GET") {
            $authorization = "Authorization: Bearer " . $request['token'];
            $url = $request['request']['uri'];
        } else {
            $args = $request->getHeaders();
            $authorization = "Authorization:" . $args['Authorization'];
            $url = $request->getUri();
        }

        $headers = array(
            'Content-Type: application/vnd.ni-payment.v2+json',
            $authorization,
            'Accept: application/vnd.ni-payment.v2+json'
        );

        $ch         = curl_init();
        $curlConfig = array(
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
        );

        if (!is_array($request)) {
            if ($request->getMethod() == "POST") {
                $data  = json_encode($request->getBody());
                $curlConfig[CURLOPT_POST]       = true;
                $curlConfig[CURLOPT_POSTFIELDS] = $data;
            } else {
                $curlConfig[CURLOPT_PUT]       = true;
            }
        }

        curl_setopt_array($ch, $curlConfig);
        $response = curl_exec($ch);

        return $this->postProcess($response);
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
