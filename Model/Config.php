<?php

namespace NetworkInternational\NGenius\Model;

use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Config
{
    public const METHOD_CODE = 'ngeniusonline';
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var Data
     */
    private Data $directoryHelper;
    /**
     * Currency codes supported by Ngenius methods
     * @var string[]
     */
    private array $supportedCurrencyCodes = ['AED'];

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $directoryHelper
     */
    public function __construct(LoggerInterface $logger, ScopeConfigInterface $scopeConfig, Data $directoryHelper)
    {
        $this->logger          = $logger;
        $this->scopeConfig     = $scopeConfig;
        $this->directoryHelper = $directoryHelper;
    }


    /**
     * Store ID setter
     *
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = (int)$storeId;

        return $this;
    }

    /**
     * Method code setter
     *
     * @param string|MethodInterface $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        if ($method instanceof MethodInterface) {
            $this->methodCode = $method->getCode();
        } elseif (is_string($method)) {
            $this->methodCode = $method;
        }

        return $this;
    }

    /**
     * Store ID Getter
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Check whether specified currency code is supported
     *
     * @param string $code
     *
     * @return bool
     */
    public function isCurrencyCodeSupported($code)
    {
        $supported = false;
        $pre       = __METHOD__ . ' : ';

        $this->logger->debug($pre . "bof and code: {$code}");

        if (in_array($code, $this->supportedCurrencyCodes)) {
            $supported = true;
        }

        $this->logger->debug($pre . "eof and supported : {$supported}");

        return $supported;
    }

    /**
     * Check whether method available for checkout or not
     *
     * @param string|null $methodCode
     *
     * @return bool
     */
    public function isMethodAvailable($methodCode = null)
    {
        $methodCode = $methodCode ?: $this->methodCode;

        return $this->isMethodActive($methodCode);
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $method Method code
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isMethodActive(string $method): bool
    {
        $isEnabled = $this->scopeConfig->isSetFlag(
            "payment/{$method}/active",
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );

        return $this->isMethodSupportedForCountry($method) && $isEnabled;
    }

    /**
     * Is Method Supported For Country
     *
     * Check whether method supported for specified country or not
     * Use $methodCode and merchant country by default
     *
     * @param string|null $method
     * @param string|null $countryCode
     *
     * @return bool
     */
    public function isMethodSupportedForCountry($method = null, $countryCode = null)
    {
        if ($method === null) {
            $method = $this->getMethodCode();
        }

        if ($countryCode === null) {
            $countryCode = $this->getMerchantCountry();
        }

        return in_array($method, $this->getCountryMethods($countryCode));
    }

    /**
     * Payment method instance code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->methodCode;
    }

    /**
     * Return merchant country code, use default country if it not specified in General settings
     *
     * @return string|null
     */
    public function getMerchantCountry()
    {
        return $this->directoryHelper->getDefaultCountry($this->storeId);
    }

    /**
     * Return list of allowed methods for specified country iso code
     *
     * @param string|null $countryCode 2-letters iso code
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCountryMethods($countryCode = null)
    {
        $countryMethods = [
            'other' => [
                self::METHOD_CODE,
            ],

        ];
        if ($countryCode === null) {
            return $countryMethods;
        }

        return isset($countryMethods[$countryCode]) ? $countryMethods[$countryCode] : $countryMethods['other'];
    }
}
