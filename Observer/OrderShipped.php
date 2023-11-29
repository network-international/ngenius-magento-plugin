<?php

namespace NetworkInternational\NGenius\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use NetworkInternational\NGenius\Model\CoreFactory;
use NetworkInternational\NGenius\Gateway\Config\Config;

class OrderShipped implements ObserverInterface
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
     * Forces order into processing for ship action
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        if ($order->getPayment()->getMethodInstance()->getCode() === Config::CODE
            && $order->getState() !== Order::STATE_PROCESSING
        ) {
            $order->setStatus(Order::STATE_PROCESSING);
            $order->setState(Order::STATE_PROCESSING);
            $order->save();
        }
    }
}
