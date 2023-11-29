<?php

namespace NetworkInternational\NGenius\Observer;

use Fortis\Fortis\Model\FortisApi;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Store\Model\StoreManagerInterface;
use NetworkInternational\NGenius\Gateway\Config\Config;

class OrderCancelAfter implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\Builder
     */
    private Builder $transactionBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Config                                                 $config
     * @param \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder
     * @param StoreManagerInterface                                  $storeManager
     */

    public function __construct(Config $config, Builder $transactionBuilder, StoreManagerInterface $storeManager)
    {
        $this->config             = $config;
        $this->transactionBuilder = $transactionBuilder;
        $this->storeManager       = $storeManager;
    }

    /**
     * Handles cancelled and declined transactions
     *
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $storeId = $this->storeManager->getStore()->getId();

        if ($this->config->getCustomFailedOrderStatus($storeId) != null) {
            $status = $this->config->getCustomFailedOrderStatus($storeId);
        } else {
            $status = Order::STATE_CLOSED;
        }

        if ($this->config->getCustomFailedOrderState($storeId) != null) {
            $state = $this->config->getCustomFailedOrderState($storeId);
        } else {
            $state = Order::STATE_CLOSED;
        }

        try {
            $data  = $observer->getData();
            $order = $data['order'] ?? null;
            if (!$order) {
                return;
            }
            $payment = $order->getPayment();

            if (!empty($payment->getAdditionalInformation()['raw_details_info'])) {
                $d = json_decode($payment->getAdditionalInformation()['raw_details_info']);
                if ($d->state === 'FAILED') {
                    $order->setStatus($status);
                    $order->setState($state);
                }
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('There was a problem. ' . $e->getMessage()));
        }
    }
}
