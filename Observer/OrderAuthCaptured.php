<?php

namespace NetworkInternational\NGenius\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NetworkInternational\NGenius\Model\CoreFactory;

class OrderAuthCaptured implements ObserverInterface {

    protected CoreFactory $coreFactory;

    public function __construct(
        CoreFactory $coreFactory,
    ) {
        $this->coreFactory = $coreFactory;
    }

    public function execute(Observer $observer)
    {
        $order      = $observer->getInvoice()->getOrder();

        $paymentResult = $order->getPayment()->getAdditionalInformation("paymentResult") ?? null;

        if (!$paymentResult) {
            return;
        }

        $orderRef       = json_decode($paymentResult)->orderReference;
        $collection    = $this->coreFactory->create()->getCollection()->addFieldToFilter(
            'reference',
            $orderRef
        );
        $orderItem     = $collection->getFirstItem();

        if ($orderItem->getData()["action"] !== "AUTH") {
            return;
        }

        $order->setState($orderItem->getData()["state"]);
        $order->setStatus($orderItem->getData()["status"]);
        $order->save();
    }
}
