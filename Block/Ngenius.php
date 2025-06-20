<?php

namespace NetworkInternational\NGenius\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Sales\Api\Data\OrderInterface;
use NetworkInternational\NGenius\Gateway\Http\Client\PaymentTransaction;
use NetworkInternational\NGenius\Gateway\Request\PaymentRequest;
use NetworkInternational\NGenius\Gateway\Request\TokenRequest;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;

/**
 * Class Info
 */
class Ngenius extends ConfigurableInfo
{
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var OrderInterface
     */
    protected $orderFactory;

    /**
     * @var TokenRequest
     */
    protected $tokenRequest;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var array|string[]
     */
    private array $allowedActions = ['PURCHASE', 'AUTH', 'SALE'];

    /**
     * Ngenius constructor.
     *
     * @param OrderInterface $orderInterface
     * @param TokenRequest $tokenRequest
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param PaymentRequest $paymentRequest
     * @param PaymentTransaction $paymentTransaction
     */
    public function __construct(
        OrderInterface $orderInterface,
        TokenRequest $tokenRequest,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        PaymentRequest $paymentRequest,
        PaymentTransaction $paymentTransaction
    ) {
        $this->checkoutSession    = $checkoutSession;
        $this->orderFactory       = $orderInterface;
        $this->tokenRequest       = $tokenRequest;
        $this->_scopeConfig       = $scopeConfig;
        $this->paymentRequest     = $paymentRequest;
        $this->paymentTransaction = $paymentTransaction;
    }

    /**
     * Retrieves Pay Page URL
     *
     * @param string $ngeniusPaymentAction
     *
     * @return array
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function getPaymentUrl(string $ngeniusPaymentAction): array
    {
        $checkoutSession = $this->checkoutSession;
        $return          = [];

        if ($incrementId = $checkoutSession->getLastRealOrderId()) {
            $order = $this->orderFactory->loadByIncrementId($incrementId);

            $storeId = $order->getStoreId();
            $amount  = ValueFormatter::floatToIntRepresentation(
                $order->getOrderCurrencyCode(),
                $order->getGrandTotal()
            );

            if (in_array($ngeniusPaymentAction, $this->allowedActions)) {
                $requestData = [
                    'token'   => $this->tokenRequest->getAccessToken($storeId),
                    'request' => $this->paymentRequest->getBuildArray(
                        $order,
                        $storeId,
                        $amount,
                        $ngeniusPaymentAction
                    )
                ];

                $data = $this->paymentTransaction->placeRequest($requestData);

                if (isset($data['payment_url'])) {
                    $return = ['url' => $data['payment_url']];
                } elseif (isset($data['message'])) {
                    $return = ['exception' => new LocalizedException(__($data['message']))];
                }
            } else {
                $return = ['exception' => new LocalizedException(__('Invalid configuration.'))];
            }
        }

        return $return;
    }
}
