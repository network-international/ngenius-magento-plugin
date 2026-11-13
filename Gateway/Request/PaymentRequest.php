<?php

namespace NetworkInternational\NGenius\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Store\Model\StoreManagerInterface;
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Helper\Version;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\NgeniusUtilities;

class PaymentRequest implements BuilderInterface
{
    use Formatter;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var TokenRequest
     */
    protected TokenRequest $tokenRequest;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var Session
     */
    protected Session $checkoutSession;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlBuilder;

    /**
     * PaymentRequest constructor.
     *
     * @param Config $config
     * @param TokenRequest $tokenRequest
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Config $config,
        TokenRequest $tokenRequest,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        UrlInterface $urlBuilder
    ) {
        $this->config          = $config;
        $this->tokenRequest    = $tokenRequest;
        $this->storeManager    = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder      = $urlBuilder;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws CouldNotSaveException
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $paymentDO->getPayment()->setIsTransactionPending(true);
        $order       = $paymentDO->getOrder();
        $storeId     = $order->getStoreId();
        $formatPrice = $this->formatPrice(SubjectReader::readAmount($buildSubject));
        $amount      = ValueFormatter::floatToIntRepresentation($order->getCurrencyCode(), $formatPrice);

        if ($this->config->isComplete($storeId)) {
            $this->setTableData($order);

            return [
                'token'   => $this->tokenRequest->getAccessToken($storeId),
                'request' => $this->getBuildArray($order, $storeId, $amount)
            ];
        } else {
            throw new CouldNotSaveException(__('Invalid configuration.'));
        }
    }

    /**
     * Set Table Data
     *
     * @param object $order
     *
     * @return void
     */
    protected function setTableData($order): void
    {
        $data = [
            'order_id' => $order->getOrderIncrementId(),
            'currency' => $order->getCurrencyCode(),
            'amount'   => $order->getGrandTotalAmount()
        ];
        $this->checkoutSession->setTableData($data);
    }

    /**
     * Gets array of data for API request
     *
     * @param object $order
     * @param int $storeId
     * @param float $amount
     * @param string $action
     *
     * @return array
     */
    public function getBuildArray($order, $storeId, $amount, $action): array
    {
        $currencyCode = $order->getOrderCurrencyCode();
        $countryCode  = $order->getBillingAddress()->getCountryId();
        $utilities    = new NgeniusUtilities();

        return [
            'data'   => [
                'action'                 => $action,
                'amount'                 => [
                    'currencyCode' => $currencyCode,
                    'value'        => $amount,
                ],
                'merchantAttributes'     => [
                    'redirectUrl'          => $this->urlBuilder->getDirectUrl(
                        "networkinternational/ngeniusonline/payment"
                    ),
                    'skipConfirmationPage' => true,
                    'cancelUrl'            => $this->urlBuilder->getDirectUrl(
                        "networkinternational/ngeniusonline/cancel"
                    ),
                    'cancelText'           => (string)__('Continue Shopping'),
                ],
                'merchantOrderReference' => $order->getRealOrderId(),
                'emailAddress'           => $order->getBillingAddress()->getEmail(),
                'billingAddress'         => [
                    'firstName'   => $order->getBillingAddress()->getFirstName(),
                    'lastName'    => $order->getBillingAddress()->getLastName(),
                    'address1'    => $order->getBillingAddress()->getStreetLine(1),
                    'address2'    => $order->getBillingAddress()->getStreetLine(2),
                    'city'        => $order->getBillingAddress()->getCity(),
                    'stateCode'   => $order->getBillingAddress()->getRegion(),
                    'postalCode'  => $order->getBillingAddress()->getPostcode(),
                    'countryCode' => $countryCode,

                ],
                'phoneNumber'            => [
                    'countryCode' => $utilities->getCountryTelephonePrefix($countryCode),
                    'subscriber'  => $order->getBillingAddress()->getTelephone(),
                ],
                'merchantDefinedData'    => [
                    'pluginName'    => 'magento-2',
                    'pluginVersion' => Version::MODULE_VERSION
                ]
            ],
            'method' => \Laminas\Http\Request::METHOD_POST,
            'uri'    => $this->config->getOrderRequestURL($storeId, $action, $currencyCode),
        ];
    }
}
