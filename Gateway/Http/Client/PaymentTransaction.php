<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use NetworkInternational\NGenius\Setup\Patch\Data\DataPatch;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;
use NetworkInternational\NGenius\Gateway\Config\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use NetworkInternational\NGenius\Model\CoreFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class PaymentTransaction implements ClientInterface
{
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var Session
     */
    protected Session $checkoutSession;
    /**
     * @var array|\string[][]
     */
    protected array $orderStatus;
    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;
    /**
     * @var Config
     */
    protected Config $config;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var CoreFactory
     */
    protected CoreFactory $coreFactory;
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * PaymentTransaction constructor.
     *
     * @param Logger $logger
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param CoreFactory $coreFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Logger $logger,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        Config $config,
        StoreManagerInterface $storeManager,
        CoreFactory $coreFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->logger          = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->orderStatus     = DataPatch::getStatuses();
        $this->messageManager  = $messageManager;
        $this->config          = $config;
        $this->storeManager    = $storeManager;
        $this->coreFactory     = $coreFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param array|TransferInterface $requestData
     *
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function placeRequest(array|TransferInterface $requestData): ?array
    {
        if (is_array($requestData)) {
            $token  = $requestData['token'];
            $url    = $requestData['request']['uri'];
            $data   = $requestData['request']['data'];
            $method = $requestData['request']['method'];
        } else {
            $token  = $requestData->getHeaders()['Token'];
            $url    = $requestData->getUri();
            $data   = $requestData->getBody();
            $method = $requestData->getMethod();
        }

        $storeId             = $this->storeManager->getStore()->getId();
        $ngeniusHttpTransfer = new NgeniusHTTPTransfer($url, $this->config->getHttpVersion($storeId));
        $ngeniusHttpTransfer->setPaymentHeaders($token);
        $ngeniusHttpTransfer->setMethod($method);
        $ngeniusHttpTransfer->setData($data);

        $response = NgeniusHTTPCommon::placeRequest($ngeniusHttpTransfer);

        return $this->postProcess($response);
    }

    /**
     * Processing of API request body
     *
     * @param array $data
     *
     * @return string
     */
    protected function preProcess(array $data): string
    {
        return json_encode($data);
    }

    /**
     * Processing of API response
     *
     * @param string $responseEnc
     *
     * @return null|array
     * @throws Exception
     */
    protected function postProcess(string $responseEnc): ?array
    {
        $response = json_decode($responseEnc);
        if (isset($response->_links->payment->href)) {
            $data = $this->checkoutSession->getData();

            $amount = (float)$response->amount->value;

            $currencyCode = $response->amount->currencyCode;

            $data['reference'] = $response->reference ?? '';
            $data['action']    = $response->action ?? '';
            $data['amount']    = ValueFormatter::formatOrderStatusAmount($currencyCode, $amount) / 100;
            $data['state']     = $response->_embedded->payment[0]->state ?? '';
            $data['status']    = $this->orderStatus[0]['status'];
            $data['order_id']  = $data['last_real_order_id'];
            $data['entity_id'] = $data['last_order_id'];
            $data['currency']  = $response->amount->currencyCode;

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
}
