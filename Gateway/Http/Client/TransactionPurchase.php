<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

use Magento\Checkout\Model\Session;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Model\Method\Logger;
use NetworkInternational\NGenius\Model\CoreFactory;

/*
 * Class TransactionPurchase
 */

class TransactionPurchase
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
        $this->logger          = $logger;
        $this->clientFactory   = $clientFactory;
        $this->checkoutSession = $checkoutSession;
        $this->coreFactory     = $coreFactory;
        $this->orderStatus     = \NetworkInternational\NGenius\Setup\InstallData::getStatuses();
        $this->messageManager  = $messageManager;
    }

    /**
     * Processing of API response
     *
     * @param array $responseEnc
     *
     * @return null|array
     */
    protected function postProcess($responseEnc)
    {
        $response = json_decode($responseEnc);
        if (isset($response->_links->payment->href)) {
            $data = $this->checkoutSession->getData();

            $data['reference'] = $response->reference ?? '';
            $data['action']    = $response->action ?? '';
            $data['state']     = $response->_embedded->payment[0]->state ?? '';
            $data['status']    = $this->orderStatus[0]['status'];
            $data['order_id']  = $data['last_real_order_id'];
            $data['entity_id'] = $data['last_order_id'];
            $data['currency'] = $data['table_data']['currency'];

            $model = $this->coreFactory->create();
            $model->addData($data);
            $model->save();

            $this->checkoutSession->setPaymentURL($response->_links->payment->href);

            return ['payment_url' => $response->_links->payment->href];
        } elseif (isset($response->errors)) {
            return ['message' => 'Message: ' . $response->message . ': ' . $response->errors[0]->message];
        } else {
            return null;
        }
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param $request
     *
     * @return array|null
     */
    public function placeRequest($request): ?array
    {
        $authorization = "Authorization: Bearer " . $request['token'];
        $url           = $request['request']['uri'];

        $headers = [
            'Content-Type: application/vnd.ni-payment.v2+json',
            $authorization,
            'Accept: application/vnd.ni-payment.v2+json'
        ];

        $data = json_encode($request['request']['data']);

        $ch         = curl_init();
        $curlConfig = [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data
        ];

        curl_setopt_array($ch, $curlConfig);
        $response = curl_exec($ch);

        return $this->postProcess($response);
    }
}
