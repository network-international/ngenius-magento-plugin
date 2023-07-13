<?php

namespace NetworkInternational\NGenius\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use NetworkInternational\NGenius\Model\CoreFactory;

/**
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
    public const FETCH_ENDPOINT          = 'fetch_endpoint';
    public const CAPTURE_ENDPOINT        = 'capture_endpoint';
    public const REFUND_ENDPOINT         = 'refund_endpoint';
    public const VOID_ENDPOINT           = 'void_auth_endpoint';
    public const DEBUG                   = 'debug';
    public const HTTP_VERSION            = 'http_version';
    public const SUCCESS_ORDER_STATE     = 'success_order_state';
    public const SUCCESS_ORDER_STATUS    = 'success_order_status';
    /**
     * @var \NetworkInternational\NGenius\Model\CoreFactory
     */
    private CoreFactory $coreFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CoreFactory $coreFactory,
        $pathPattern = \Magento\Payment\Gateway\Config\Config::DEFAULT_PATH_PATTERN,
        $methodCode = null,
    ) {
        \Magento\Payment\Gateway\Config\Config::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->coreFactory = $coreFactory;
    }

    /**
     * Gets value of configured environment.
     * Possible values: live or uat.
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
     * get payment action.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function getPaymentAction($storeId = null): bool
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
     * Gets order request URL.
     *
     * @param int|null $storeId
     * @param $action
     *
     * @return string
     */
    public function getOrderRequestURL(?int $storeId, $action, $currencyCode)
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
     * Gets fetch URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getFetchRequestURL($orderRef, $storeId = null)
    {
        $endpoint = sprintf(
            $this->getValue(Config::FETCH_ENDPOINT, $storeId),
            $this->getTrueOutletReferenceId($orderRef, $storeId),
            $orderRef
        );

        return $this->getApiUrl($storeId) . $endpoint;
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
     * Gets capture URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOrderCaptureURL($orderRef, $paymentRef, $storeId = null)
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
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOrderRefundURL($orderRef, $paymentRef, $transactionId, $storeId = null)
    {
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
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOrderVoidURL($orderRef, $paymentRef, $storeId = null)
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
     * @param $orderRef
     * @param $storeId
     *
     * @return string
     */
    private function getTrueOutletReferenceId($orderRef, $storeId): string
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
}
