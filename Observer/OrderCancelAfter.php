<?php

namespace NetworkInternational\NGenius\Observer;

use Fortis\Fortis\Model\FortisApi;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;

class OrderCancelAfter implements ObserverInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\Builder
     */
    private Builder $transactionBuilder;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Builder $transactionBuilder)
    {
        $this->scopeConfig        = $scopeConfig;
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer): void
    {
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
                    $order->setStatus('ngenius_declined');
                    $order->setState(Order::STATE_CLOSED);
                }
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('There was a problem. ' . $e->getMessage()));
        }
    }
}
