<?php

namespace NetworkInternational\NGenius\Gateway\Request;

use NetworkInternational\NGenius\Gateway\Config\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\Exception\LocalizedException;
use NetworkInternational\NGenius\Gateway\Request\TokenRequest;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use NetworkInternational\NGenius\Model\CoreFactory;
use Magento\Payment\Helper\Formatter;

class VoidRequest implements BuilderInterface
{

    use Formatter;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var TokenRequest
     */
    protected $tokenRequest;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CoreFactory
     */
    protected $coreFactory;

    /**
     * VoidRequest constructor.
     *
     * @param Config $config
     * @param TokenRequest $tokenRequest
     * @param StoreManagerInterface $storeManager
     * @param CoreFactory $coreFactory
     */
    public function __construct(
        Config $config,
        TokenRequest $tokenRequest,
        StoreManagerInterface $storeManager,
        CoreFactory $coreFactory
    ) {
        $this->config = $config;
        $this->tokenRequest = $tokenRequest;
        $this->storeManager = $storeManager;
        $this->coreFactory = $coreFactory;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @throws LocalizedException
     * @return array
     */
    public function build(array $buildSubject)
    {

        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();

        $transactionId = $payment->getTransactionId();

        if (!$transactionId) {
            throw new LocalizedException(__('No authorization transaction to proceed.'));
        }

        $coreFactory = $this->coreFactory->create();
        $collection = $coreFactory->getCollection()->addFieldToFilter('order_id', $order->getOrderIncrementId());
        $orderItem = $collection->getFirstItem();

        if ($this->config->isComplete($storeId)) {
            return[
                'token' => $this->tokenRequest->getAccessToken($storeId),
                'request' => [
                    'data' => [],
                    'method' => \Zend_Http_Client::PUT,
                    'uri' => $this->config->getOrderVoidURL($orderItem->getReference(), $orderItem->getPaymentId(), $storeId)
                ]
            ];
        } else {
            throw new LocalizedException(__('Invalid configuration.'));
        }
    }
}
