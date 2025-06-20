<?php

namespace NetworkInternational\NGenius\Service;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Setup\Patch\Data\DataPatch;

class OrderStatusService
{
    private const STATE_PBL_STARTED = 'PBL_STARTED';
    private Config $config;
    private OrderRepositoryInterface $orderRepository;

    /**
     * @param Config $config
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(Config $config, OrderRepositoryInterface $orderRepository)
    {
        $this->config          = $config;
        $this->orderRepository = $orderRepository;
    }

    public function isNgeniusPayment(Order $order): bool
    {
        return $order->getPayment() && $order->getPayment()->getMethod() === 'ngeniusonline';
    }

    public function setInitialStatus(Order $order): void
    {
        $storeId       = $order->getStoreId();
        $initialStatus = $this->config->getInitialOrderStatus($storeId);
        $order->setState($initialStatus);
        $order->setStatus($initialStatus);
        $order->addCommentToStatusHistory(__('N-Genius payment initiated.'));
        $this->orderRepository->save($order);
    }

    public function getDefaultStatus(): string
    {
        return DataPatch::getStatuses()[0]['status'];
    }

    public function getDefaultPBLState(): string
    {
        return self::STATE_PBL_STARTED;
    }
}
