<?php

namespace NetworkInternational\NGenius\Gateway\Request;

use NetworkInternational\NGenius\Gateway\Config\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Helper\Formatter;
use Laminas\Http\Request;

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
     * VoidRequest constructor.
     *
     * @param Config $config
     * @param TokenRequest $tokenRequest
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        TokenRequest $tokenRequest,
        StoreManagerInterface $storeManager,
    ) {
        $this->config       = $config;
        $this->tokenRequest = $tokenRequest;
        $this->storeManager = $storeManager;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment   = $paymentDO->getPayment();
        $order     = $paymentDO->getOrder();
        $storeId   = $order->getStoreId();

        $paymentResult = json_decode($payment->getAdditionalInformation('paymentResult'));

        $transactionId = $paymentResult->reference;
        $orderId       = $paymentResult->orderReference;

        if (!$transactionId) {
            throw new LocalizedException(__('No authorization transaction to proceed.'));
        }

        if ($this->config->isComplete($storeId)) {
            return [
                'token'   => $this->tokenRequest->getAccessToken($storeId),
                'request' => [
                    'data'   => [],
                    'method' => \Laminas\Http\Request::METHOD_PUT,
                    'uri'    => $this->config->getOrderVoidURL(
                        $orderId,
                        $transactionId,
                        $storeId
                    )
                ]
            ];
        } else {
            throw new LocalizedException(__('Invalid configuration.'));
        }
    }
}
