<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

use Magento\Checkout\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use NetworkInternational\NGenius\Gateway\Config\Config;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use NetworkInternational\NGenius\Model\CoreFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

/*
 * Class TransactionVoid
 */

class TransactionVoid extends PaymentTransaction
{
    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;
    /**
     * @var Builder
     */
    private Builder $transactionBuilder;

    /**
     * @param Logger $logger
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     * @param OrderFactory $orderFactory
     * @param Builder $transactionBuilder
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param CoreFactory $coreFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Logger $logger,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        OrderFactory $orderFactory,
        Builder $transactionBuilder,
        Config $config,
        StoreManagerInterface $storeManager,
        CoreFactory $coreFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderFactory       = $orderFactory;
        $this->transactionBuilder = $transactionBuilder;
        parent::__construct(
            $logger,
            $checkoutSession,
            $messageManager,
            $config,
            $storeManager,
            $coreFactory,
            $orderRepository
        );
    }

    /**
     * Processing of API request body
     *
     * @param array $data
     *
     * @return string
     */
    protected function preProcess(array $data): string
    {
        return json_encode($data);
    }

    /**
     * Processing of API response
     *
     * @param array $responseEnc
     *
     * @return array
     * @throws \Exception
     */
    protected function postProcess($responseEnc): ?array
    {
        $response = json_decode($responseEnc, true);

        if (isset($response['errors']) && is_array($response['errors'])) {
            return [];
        } else {
            $collection = $this->coreFactory->create()
                                            ->getCollection()
                                            ->addFieldToFilter('reference', $response['orderReference']);

            $orderItem = $collection->getFirstItem();

            $state        = isset($response['state']) ? $response['state'] : '';
            $order_status = ($state == 'REVERSED') ? $this->orderStatus[9]['status'] : '';

            $orderItem->setState($state);
            $orderItem->setStatus($order_status);
            $orderItem->save();

            $trans = $this->transactionBuilder;

            $order   = $this->orderFactory->create()->loadByIncrementId($orderItem->getData('order_id'));
            $payment = $order->getPayment();
            $payment->setAdditionalInformation(['voidResponse' => $responseEnc]);
            $payment->setAmountAuthorized(0.00);

            $transaction = $trans->setPayment($payment)
                                 ->setOrder($order)
                                 ->setFailSafe(true)
                                 ->build(TransactionInterface::TYPE_VOID);

            $message = __('The authorised amount has been voided');
            $payment->addTransactionCommentsToOrder($transaction, $message);
            $payment->save();

            $order->setStatus('ngenius_auth_reversed');
            $order->setState(Order::STATE_CLOSED);
            $order->setShouldCloseParentTransaction(true);
            $order->save();
        }

        return [];
    }
}
