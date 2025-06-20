<?php

namespace NetworkInternational\NGenius\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use NetworkInternational\NGenius\Model\CoreFactory;

/**
 * NGenius config class to define the plugin's abilities
 *
 * Class Config
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    /*
     * Payment code
     */

    public const CODE = 'ngeniusonline';
    /*
     * Config tags
     */
    public const ENVIRONMENT             = 'environment';
    public const ACTIVE                  = 'active';
    public const OUTLET_REF              = 'outlet_ref';
    public const OUTLET_REF_2            = 'outlet_ref_2';
    public const OUTLET_REF_2_CURRENCIES = 'outlet_ref_2_currencies';
    public const API_KEY                 = 'api_key';
    public const PAYMENT_ACTION          = 'ngenius_payment_action';
    public const UAT_IDENTITY_URL        = 'uat_identity_url';
    public const LIVE_IDENTITY_URL       = 'live_identity_url';
    public const UAT_API_URL             = 'uat_api_url';
    public const LIVE_API_URL            = 'live_api_url';
    public const TOKEN_ENDPOINT          = '/identity/auth/access-token';
    public const ORDER_ENDPOINT          = 'order_endpoint';
    public const PAYBYLINK_ENDPOINT      = 'paybylink_endpoint';
    public const FETCH_ENDPOINT          = 'fetch_endpoint';
    public const CAPTURE_ENDPOINT        = 'capture_endpoint';
    public const REFUND_ENDPOINT         = 'refund_endpoint';
    public const VOID_ENDPOINT           = 'void_auth_endpoint';
    public const DEBUG                   = 'debug';
    public const DEBUG_CRON              = 'debug_cron';
    public const HTTP_VERSION            = 'http_version';
    public const SUCCESS_ORDER_STATE     = 'success_order_state';
    public const SUCCESS_ORDER_STATUS    = 'success_order_status';
    public const FAILED_ORDER_STATE      = 'failed_order_state';
    public const FAILED_ORDER_STATUS     = 'failed_order_status';
    public const INVOICE_EMAIL           = 'invoice_email';
    public const ORDER_EMAIL             = 'order_email';
    public const INITIAL_ORDER_STATUS    = 'ngenius_initial_order_status';
    public const REFUND_STATUS           = 'refund_statuses';
    /**
     * @var CoreFactory
     */
    private CoreFactory $coreFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CoreFactory $coreFactory
     * @param string $pathPattern
     * @param ?string $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CoreFactory $coreFactory,
        string $pathPattern = \Magento\Payment\Gateway\Config\Config::DEFAULT_PATH_PATTERN,
        ?string $methodCode = null,
    ) {
        \Magento\Payment\Gateway\Config\Config::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->coreFactory = $coreFactory;
    }

    /**
     * Check is active.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)$this->getValue(Config::ACTIVE, $storeId);
    }

    /**
     * Get payment action.
     *
     * @param ?int $storeId
     *
     * @return string
     */
    public function getPaymentAction(?int $storeId = null): string
    {
        return $this->getValue(Config::PAYMENT_ACTION, $storeId);
    }

    /**
     * Check is complete.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isComplete($storeId = null)
    {
        $complete = false;
        if (!empty($this->getApiKey($storeId)) && !empty($this->getOutletReferenceId($storeId))) {
            $complete = true;
        }

        return $complete;
    }

    /**
     * Gets Api Key.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiKey($storeId = null)
    {
        return $this->getValue(Config::API_KEY, $storeId);
    }

    /**
     * Gets Outlet Reference ID.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOutletReferenceId(int $storeId = null): string
    {
        return $this->getValue(Config::OUTLET_REF, $storeId);
    }

    /**
     * Gets token request URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getTokenRequestURL($storeId = null)
    {
        return $this->getApiUrl($storeId) . self::TOKEN_ENDPOINT;
    }

    /**
     * Gets API URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiUrl($storeId = null)
    {
        $value = Config::UAT_API_URL;

        if ($this->getEnvironment($storeId) == "live") {
            $value = Config::LIVE_API_URL;
        }

        return $this->getValue($value, $storeId);
    }

    /**
     * Gets value of configured environment. Possible values: live or uat.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getEnvironment($storeId = null)
    {
        return $this->getValue(Config::ENVIRONMENT, $storeId);
    }

    /**
     * Gets initial order status setting.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getInitialOrderStatus($storeId = null)
    {
        return $this->getValue(self::INITIAL_ORDER_STATUS, $storeId);
    }

    /**
     * Gets order request URL.
     *
     * @param int|null $storeId
     * @param string $action
     * @param string $currencyCode
     *
     * @return string
     */
    public function getOrderRequestURL(?int $storeId, string $action, string $currencyCode): string
    {
        $outlet2ReferenceId         = $this->getValue(self::OUTLET_REF_2, $storeId);
        $outlet2ReferenceCurrencies = $this->getValue(self::OUTLET_REF_2_CURRENCIES, $storeId) ?? '';
        $outlet2ReferenceCurrencies = explode(',', $outlet2ReferenceCurrencies);

        if ($outlet2ReferenceId && in_array($currencyCode, $outlet2ReferenceCurrencies)) {
            $endpoint = sprintf(
                $this->getValue(Config::ORDER_ENDPOINT, $storeId),
                $this->getOutletReference2Id($storeId)
            );
        } else {
            $endpoint = sprintf(
                $this->getValue(Config::ORDER_ENDPOINT, $storeId),
                $this->getOutletReferenceId($storeId)
            );
        }

        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets Outlet Reference 2 ID.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOutletReference2Id(int $storeId = null): string
    {
        return $this->getValue(self::OUTLET_REF_2, $storeId);
    }

    /**
     * Gets PayByLink URL.
     *
     * @param int|null $storeId
     * @param string $action
     * @param string $currencyCode
     *
     * @return string
     */
    public function getPayByLinkUrl(?int $storeId, string $action, string $currencyCode)
    {
        $outlet2ReferenceId         = $this->getValue(self::OUTLET_REF_2, $storeId);
        $outlet2ReferenceCurrencies = $this->getValue(self::OUTLET_REF_2_CURRENCIES, $storeId) ?? '';
        $outlet2ReferenceCurrencies = explode(',', $outlet2ReferenceCurrencies);

        if ($outlet2ReferenceId && in_array($currencyCode, $outlet2ReferenceCurrencies)) {
            $endpoint = sprintf(
                $this->getValue(Config::PAYBYLINK_ENDPOINT, $storeId),
                $this->getOutletReference2Id($storeId)
            );
        } else {
            $endpoint = sprintf(
                $this->getValue(Config::PAYBYLINK_ENDPOINT, $storeId),
                $this->getOutletReferenceId($storeId)
            );
        }

        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets fetch URL.
     *
     * @param string $orderRef
     * @param int|null $storeId
     *
     * @return string
     */
    public function getFetchRequestURL(string $orderRef, ?int $storeId = null): string
    {
        $endpoint = sprintf(
            $this->getValue(Config::FETCH_ENDPOINT, $storeId),
            $this->getTrueOutletReferenceId($orderRef, $storeId),
            $orderRef
        );

        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets true outlet ID for order from Magento DB
     *
     * @param string $orderRef
     * @param int|null $storeId
     *
     * @return string
     */
    private function getTrueOutletReferenceId(string $orderRef, ?int $storeId): string
    {
        $collection   = $this->coreFactory->create()->getCollection()->addFieldToFilter(
            'reference',
            $orderRef
        );
        $orderItem    = $collection->getFirstItem();
        $currencyCode = $orderItem->getDataByKey('currency');

        $outlet2ReferenceId         = $this->getValue(self::OUTLET_REF_2, $storeId);
        $outlet2ReferenceCurrencies = $this->getValue(self::OUTLET_REF_2_CURRENCIES, $storeId) ?? '';
        $outlet2ReferenceCurrencies = explode(',', $outlet2ReferenceCurrencies);

        $trueOutletReference = $this->getOutletReferenceId($storeId);

        if ($outlet2ReferenceId && in_array($currencyCode, $outlet2ReferenceCurrencies)) {
            $trueOutletReference = $outlet2ReferenceId;
        }

        return $trueOutletReference;
    }

    /**
     * Checks debug on.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function isDebugOn($storeId = null)
    {
        return (bool)$this->getValue(Config::DEBUG, $storeId);
    }

    /**
     * Checks debug cron on.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function isDebugCron($storeId = null)
    {
        return (bool)$this->getValue(Config::DEBUG_CRON, $storeId);
    }

    /**
     * Gets capture URL.
     *
     * @param string $orderRef
     * @param string $paymentRef
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOrderCaptureURL(string $orderRef, string $paymentRef, ?int $storeId = null)
    {
        $endpoint = sprintf(
            $this->getValue(Config::CAPTURE_ENDPOINT, $storeId),
            $this->getTrueOutletReferenceId($orderRef, $storeId),
            $orderRef,
            $paymentRef
        );

        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets refund URL.
     *
     * @param string $orderRef
     * @param string $paymentRef
     * @param string $transactionId
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOrderRefundURL(
        string $orderRef,
        string $paymentRef,
        string $transactionId,
        ?int $storeId = null
    ): string {
        $endpoint = sprintf(
            $this->getValue(Config::REFUND_ENDPOINT, $storeId),
            $this->getTrueOutletReferenceId($orderRef, $storeId),
            $orderRef,
            $paymentRef,
            $transactionId
        );

        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets void URL.
     *
     * @param string $orderRef
     * @param string $paymentRef
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOrderVoidURL(string $orderRef, string $paymentRef, int $storeId = null): string
    {
        $endpoint = sprintf(
            $this->getValue(Config::VOID_ENDPOINT, $storeId),
            $this->getTrueOutletReferenceId($orderRef, $storeId),
            $orderRef,
            $paymentRef
        );

        $endpoint = str_replace('//', '/', $endpoint);

        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets selected http version value..
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getHttpVersion(int $storeId = null): string
    {
        return $this->getValue(Config::HTTP_VERSION, $storeId);
    }

    /**
     * Gets selected custom success order state
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getCustomSuccessOrderState(int $storeId = null): ?string
    {
        return $this->getValue(Config::SUCCESS_ORDER_STATE, $storeId);
    }

    /**
     * Gets selected custom success order status
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getCustomSuccessOrderStatus(int $storeId = null): ?string
    {
        return $this->getValue(Config::SUCCESS_ORDER_STATUS, $storeId);
    }

    /**
     * Gets selected custom failed order state
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getCustomFailedOrderState(int $storeId = null): ?string
    {
        return $this->getValue(Config::FAILED_ORDER_STATE, $storeId);
    }

    /**
     * Gets selected custom failed order status
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getCustomFailedOrderStatus(int $storeId = null): ?string
    {
        return $this->getValue(Config::FAILED_ORDER_STATUS, $storeId);
    }

    /**
     * Gets Invoice Sender
     *
     * @param int|null $storeId
     *
     * @return bool|null
     */
    public function getInvoiceSend(int $storeId = null): ?bool
    {
        return $this->getValue(Config::INVOICE_EMAIL, $storeId);
    }

    /**
     * Gets Order Sender Timing
     *
     * @param int|null $storeId
     *
     * @return bool|null
     */
    public function getEmailSend(int $storeId = null): ?bool
    {
        return $this->getValue(Config::ORDER_EMAIL, $storeId);
    }

    /**
     * Gets N-Genius Refund Status
     *
     * @param int|null $storeId
     *
     * @return bool|null
     */
    public function getIsNgeniusRefundStatus(int $storeId = null): ?bool
    {
        return $this->getValue(Config::REFUND_STATUS, $storeId);
    }
}
