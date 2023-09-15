<?php

namespace NetworkInternational\NGenius\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NetworkInternational\NGenius\Model\CoreFactory;

class OrderAuthCaptured implements ObserverInterface
{

    /**
     * @var CoreFactory
     */
    protected CoreFactory $coreFactory;

    /**
     * @param CoreFactory $coreFactory
     */
    public function __construct(
        CoreFactory $coreFactory,
    ) {
        $this->coreFactory = $coreFactory;
    }

    /**
     * Order capture observer to set custom order statuses accordingly
     *
     * @param Observer $observer
     * @return void
     */
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

        if ($orderItem->getData()["action"] !== "AUTH"
            || (int)($orderItem->getData()["captured_amt"]) === 0
        ) {
            return;
        }

        $order->setState($orderItem->getData()["state"]);
        $order->setStatus($orderItem->getData()["status"]);
        $order->save();
    }
}
