<?php

namespace NetworkInternational\NGenius\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Model\CoreFactory;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;
use Ngenius\NgeniusCommon\Processor\RefundProcessor;

/**
 * Class responsible for generating refund request structure
 *
 * Class RefundRequest
 */
class RefundRequest implements BuilderInterface
{
    use Formatter;

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore

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
     * @var OrderInterface
     */
    protected $_orderInterface;

    /**
     * RefundRequest constructor.
     *
     * @param Config $config
     * @param TokenRequest $tokenRequest
     * @param StoreManagerInterface $storeManager
     * @param CoreFactory $coreFactory
     * @param OrderInterface $orderInterface
     */
    public function __construct(
        Config $config,
        TokenRequest $tokenRequest,
        StoreManagerInterface $storeManager,
        CoreFactory $coreFactory,
        OrderInterface $orderInterface
    ) {
        $this->config          = $config;
        $this->tokenRequest    = $tokenRequest;
        $this->storeManager    = $storeManager;
        $this->coreFactory     = $coreFactory;
        $this->_orderInterface = $orderInterface;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment   = $paymentDO->getPayment();
        $order     = $paymentDO->getOrder();
        $storeId   = $order->getStoreId();

        $paymentResult = json_decode($payment->getAdditionalInformation('paymentResult'));

        $transactionId  = $paymentResult->reference;
        $orderReference = $paymentResult->orderReference;

        if (!$transactionId) {
            throw new LocalizedException(__('No capture transaction to proceed refund.'));
        }

        $incrementId = $order->getOrderIncrementId();

        $order_details = $this->_orderInterface->loadByIncrementId($incrementId);

        $token = $this->tokenRequest->getAccessToken($storeId);
        list($refund_url, $method, $error) = $this->getRefundUrl($token, $orderReference);

        $amount       = $this->formatPrice(SubjectReader::readAmount($buildSubject)) * 100;
        $currencyCode = $order_details->getOrderCurrencyCode();

        ValueFormatter::formatCurrencyAmount($currencyCode, $amount);

        if ($error) {
            throw new LocalizedException(__($error));
        }

        if ($this->config->isComplete($storeId)) {
            return [
                'token'   => $token,
                'request' => [
                    'data'   => [
                        'amount'              => [
                            'currencyCode' => $currencyCode,
                            'value'        => $amount
                        ],
                        'merchantDefinedData' => [
                            'pluginName'    => 'magento-2',
                            'pluginVersion' => '1.1.5'
                        ]
                    ],
                    'method' => $method,
                    'uri'    => $refund_url
                ]
            ];
        } else {
            throw new LocalizedException(__('Invalid configuration.'));
        }
    }

    /**
     * Retrieves the refund object response and processes refund URL
     *
     * @param string $token
     * @param string $order_ref
     *
     * @return array Get response from api for order ref code end
     * Get response from api for order ref code end
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRefundUrl(string $token, string $order_ref): array
    {
        $method = "POST";

        $response = $this->getResponseApi($token, $order_ref);

        if (isset($response->errors)) {
            return [null, null, $response->errors[0]->message];
        }

        $payment = $response->_embedded->payment[0];

        $refund_url = RefundProcessor::extractUrl($payment);

        if (!$refund_url) {
            throw new LocalizedException(__('Refund data not found.'));
        }

        return [$refund_url, $method, null];
    }

    /**
     * Gets API response
     *
     * @param string $token
     * @param string $order_ref
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getResponseApi(
        string $token,
        string $order_ref
    ) {
        $url = $this->config->getFetchRequestURL($order_ref);

        $storeId             = $this->storeManager->getStore()->getId();
        $ngeniusHttpTransfer = new NgeniusHTTPTransfer($url, $this->config->getHttpVersion($storeId));
        $ngeniusHttpTransfer->setMethod('GET');
        $ngeniusHttpTransfer->setPaymentHeaders($token);
        $response = NgeniusHTTPCommon::placeRequest($ngeniusHttpTransfer);

        return json_decode($response);
    }
}
