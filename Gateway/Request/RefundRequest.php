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
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
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

        if ($error) {
            throw new LocalizedException(__($error));
        }

        if ($this->config->isComplete($storeId)) {
            return [
                'token'   => $token,
                'request' => [
                    'data'   => [
                        'amount' => [
                            'currencyCode' => $order_details->getOrderCurrencyCode(),
                            'value'        => $this->formatPrice(SubjectReader::readAmount($buildSubject)) * 100
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
     * @param $token
     * @param $order_ref
     *
     * @return array Get response from api for order ref code end
     * Get response from api for order ref code end
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRefundUrl($token, $order_ref): array
    {
        $method = "POST";

        $response = $this->getResponseApi($token, $order_ref);

        if (isset($response->errors)) {
            return [null, null, $response->errors[0]->message];
        }

        $cnpcapture = "cnp:capture";
        $cnprefund  = 'cnp:refund';
        $cnpcancel  = 'cnp:cancel';

        $payment = $response->_embedded->payment[0];

        $refund_url = "";
        if ($payment->state == "PURCHASED") {
            if (isset($payment->_links->$cnpcancel->href)) {
                $refund_url = $payment->_links->$cnpcancel->href;
                $method     = 'PUT';
            } elseif (isset($payment->_links->$cnprefund->href)) {
                $refund_url = $payment->_links->$cnprefund->href;
            }
        } elseif ($payment->state == "CAPTURED") {
            if (isset($payment->_embedded->$cnpcapture[0]->_links->$cnprefund->href)) {
                $refund_url = $payment->_embedded->$cnpcapture[0]->_links->$cnprefund->href;
            } else {
                $refund_url = $payment->_embedded->$cnpcapture[0]->_links->self->href . '/refund';
            }
        } else {
            if (isset($payment->_links->$cnprefund->href)) {
                $refund_url = $payment->_embedded->$cnpcapture[0]->_links->$cnprefund->href;
            }
        }

        if (!$refund_url) {
            throw new LocalizedException(__('Refund data not found.'));
        }

        return [$refund_url, $method, null];
    }

    public function getResponseApi(
        $token,
        $order_ref
    ) {
        $authorization = "Authorization: Bearer " . $token;
        $url           = $this->config->getFetchRequestURL($order_ref);

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

        curl_setopt_array($ch, $curlConfig);
        $response = curl_exec($ch);

        return json_decode($response);
    }
}
