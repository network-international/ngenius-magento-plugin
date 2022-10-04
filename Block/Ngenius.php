<?php

namespace NetworkInternational\NGenius\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Sales\Api\Data\OrderInterface;
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Gateway\Http\Client\TransactionPurchase;
use NetworkInternational\NGenius\Gateway\Request\PurchaseRequest;
use NetworkInternational\NGenius\Gateway\Request\TokenRequest;

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
     * @var PurchaseRequest
     */
    protected $_purchaseRequest;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    protected $_transactionPurchase;

    private array $allowedActions = ['order', 'authorize', 'authorize_capture'];

    /**
     * Ngenius constructor.
     *
     * @param Session $checkoutSession
     */
    public function __construct(
        OrderInterface $orderInterface,
        TokenRequest $tokenRequest,
        PurchaseRequest $purchaseRequest,
        ScopeConfigInterface $scopeConfig,
        TransactionPurchase $transactionPurchase,
        Session $checkoutSession
    ) {
        $this->checkoutSession      = $checkoutSession;
        $this->orderFactory         = $orderInterface;
        $this->tokenRequest         = $tokenRequest;
        $this->_purchaseRequest     = $purchaseRequest;
        $this->_scopeConfig         = $scopeConfig;
        $this->_transactionPurchase = $transactionPurchase;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentUrl(): array
    {
        $checkoutSessionn = $this->checkoutSession;
        $return          = [];

        $payment_action       = $this->_scopeConfig->getValue('payment/ngeniusonline/payment_action');

        $url = $checkoutSessionn->getPaymentURL() ?? '';
        if (strpos($url, 'http') === 0  && $payment_action !== 'order') {
            return ['url' => $url];
        }


        if ($incrementId = $checkoutSessionn->getLastRealOrderId()) {
            $order = $this->orderFactory->loadByIncrementId($incrementId);

            $storeId = $order->getStoreId();
            $amount  = $order->getGrandTotal() * 100;

            $order->paymentAction = $payment_action;

            if (in_array($payment_action, $this->allowedActions)) {
                $request_data = [
                    'token'   => $this->tokenRequest->getAccessToken($storeId),
                    'request' => $this->_purchaseRequest->getBuildArray($order, $storeId, $amount)
                ];

                $data = $this->_transactionPurchase->placeRequest($request_data);

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
