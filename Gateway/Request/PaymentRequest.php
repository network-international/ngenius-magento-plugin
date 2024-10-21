<?php

namespace NetworkInternational\NGenius\Gateway\Request;

use NetworkInternational\NGenius\Gateway\Config\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Formatter;
use NetworkInternational\NGenius\Helper\Version;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\NgeniusUtilities;

class PaymentRequest implements BuilderInterface
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
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

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
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $paymentDO->getPayment()->setIsTransactionPending(true);
        $order   = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $formatPrice       = $this->formatPrice(SubjectReader::readAmount($buildSubject));
        $amount = ValueFormatter::floatToIntRepresentation($order->getCurrencyCode(), $formatPrice);

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
     * @return null
     */
    protected function setTableData($order)
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
