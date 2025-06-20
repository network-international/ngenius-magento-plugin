<?php

namespace NetworkInternational\NGenius\Model;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Block\Form;
use Magento\Payment\Block\Info;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Ngenius implements MethodInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;

    private ScopeConfigInterface $scopeConfig;
    private InfoInterface $infoInstance;
    /**
     * @var string
     */
    private $formBlockType = Form::class;

    /**
     * @var string
     */
    private $infoBlockType = Info::class;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;
    private Config $config;
    private DirectoryHelper $directoryHelper;
    private CoreFactory $configFactory;

    /**
     * Construct
     *
     * @param ManagerInterface $eventManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CoreFactory $configFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param EncryptorInterface $encryptor
     * @param OrderRepositoryInterface $orderRepository
     * @param DirectoryHelper $directoryHelper
     */
    public function __construct(
        ManagerInterface $eventManager,
        ScopeConfigInterface $scopeConfig,
        CoreFactory $configFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        EncryptorInterface $encryptor,
        OrderRepositoryInterface $orderRepository,
        DirectoryHelper $directoryHelper,
    ) {
        $this->eventManager    = $eventManager;
        $this->storeManager    = $storeManager;
        $this->urlBuilder      = $urlBuilder;
        $this->encryptor       = $encryptor;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig     = $scopeConfig;
        $this->directoryHelper = $directoryHelper;

        $parameters = ['params' => [Config::METHOD_CODE]];

        $this->config = $configFactory->create($parameters);
    }

    /**
     * @inheritDoc
     */
    public function getFormBlockType()
    {
        return $this->formBlockType;
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    /**
     * @inheritDoc
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/' . $this->getCode() . '/' . $field;

        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Order Place Redirect Url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->getCheckoutRedirectUrl();
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->urlBuilder->getUrl('ngeniusonline/redirect');
    }

    /**
     * @inheritDoc
     */
    public function getStore()
    {
        return $this->config->getStoreId();
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        return Config::METHOD_CODE;
    }

    /**
     * @inheritDoc
     */
    public function setStore($store)
    {
        if (null === $store) {
            $store = $this->storeManager->getStore()->getId();
        }
        $this->config->setStoreId(is_object($store) ? $store->getId() : $store);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function canOrder()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canAuthorize()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canCapture()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canCapturePartial()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canCaptureOnce()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canRefund()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canRefundPartialPerInvoice()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canVoid()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canUseInternal()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canUseCheckout()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canEdit()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canFetchTransactionInfo()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isGateway()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isOffline()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canUseForCountry($country)
    {
        /*
       for specific country, the flag will set up as 1
       */
        if ((int)$this->getConfigData('allowspecific') === 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry') ?? '');
            if (!in_array($country, $availableCountries)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->config->isCurrencyCodeSupported($currencyCode);
    }

    /**
     * @inheritDoc
     */
    public function getInfoBlockType()
    {
        return $this->infoBlockType;
    }

    /**
     * @inheritDoc
     */
    public function getInfoInstance()
    {
        return $this->infoInstance;
    }

    /**
     * @inheritDoc
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->infoInstance = $info;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function order(InfoInterface $payment, $amount)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function capture(InfoInterface $payment, $amount)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function refund(InfoInterface $payment, $amount)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function cancel(InfoInterface $payment)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function void(InfoInterface $payment)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canReviewPayment()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function acceptPayment(InfoInterface $payment)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function denyPayment(InfoInterface $payment)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function assignData(DataObject $data)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(CartInterface $quote = null)
    {
        return $this->config->isMethodAvailable();
    }

    /**
     * @inheritDoc
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }

    /**
     * @inheritDoc
     */
    public function initialize($paymentAction, $stateObject)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getConfigPaymentAction()
    {
        return true;
    }
}
