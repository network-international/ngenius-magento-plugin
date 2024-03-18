<?php

namespace NetworkInternational\NGenius\Observer;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\ObjectManagerInterface;

class ProductSaveAfter implements ObserverInterface
{
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ProductQty
     */
    protected $productQty;

    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var $stockRegistry
     */
    protected $stockRegistry;

    /**
     *
     * @var $productCollection
     */
    protected $productCollection;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Session $checkoutSession
     * @param ProductQty $productQty
     * @param StockManagementInterface $stockManagement
     * @param StockRegistryInterface $stockRegistry
     * @param Product $productCollection
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        ProductQty $productQty,
        StockManagementInterface $stockManagement,
        StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Model\Product $productCollection
    ) {
        $this->_objectManager    = $objectManager;
        $this->checkoutSession   = $checkoutSession;
        $this->productQty        = $productQty;
        $this->stockManagement   = $stockManagement;
        $this->stockRegistry     = $stockRegistry;
        $this->productCollection = $productCollection;
    }

    /**
     * Customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $lastRealOrder = $this->checkoutSession->getLastRealOrder();
        if ($lastRealOrder->getPayment() && $lastRealOrder->getData('state') === 'new' && ($lastRealOrder->getData(
                    'status'
                ) === "payment_review")
        ) {
            $this->checkoutSession->restoreQuote();

            //Reset
            foreach ($lastRealOrder->getAllVisibleItems() as $item) {
                $product_id = $this->productCollection->getIdBySku($item->getSku());
                $qty        = $item->getQtyOrdered();
                $this->stockManagement->backItemQty($product_id, $qty, "NULL");
            }
        }

        return true;
    }
}
