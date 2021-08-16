<?php

namespace NetworkInternational\NGenius\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class productSaveAfter implements ObserverInterface {

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
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
    \Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Checkout\Model\Session $checkoutSession, ProductQty $productQty, StockManagementInterface $stockManagement,StockRegistryInterface $stockRegistry,\Magento\Catalog\Model\Product $productCollection
    ) {
        $this->_objectManager = $objectManager;
        $this->checkoutSession = $checkoutSession;
        $this->productQty = $productQty;
        $this->stockManagement = $stockManagement;
        $this->stockRegistry = $stockRegistry;
	$this->productCollection = $productCollection;
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $lastRealOrder = $this->checkoutSession->getLastRealOrder();
        if ($lastRealOrder->getPayment()) {
            if (($lastRealOrder->getData('state') === 'new' && $lastRealOrder->getData('status') === 'pending') || $lastRealOrder->getData('status') === "payment_review") {
                $this->checkoutSession->restoreQuote();

                //Reset
                foreach ($lastRealOrder->getAllVisibleItems() as $item) {
                    $product_id = $this->productCollection->getIdBySku($item->getSku());
                    $qty = $item->getQtyOrdered();
                    $this->stockManagement->backItemQty($product_id, $qty, "NULL");
                }
            }
        }
        return true;
    }

}
