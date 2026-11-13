<?php

namespace NetworkInternational\NGenius\Controller\NGeniusOnline;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use NetworkInternational\NGenius\Gateway\Config\Config;

/**
 * Closes the unpaid order when the customer clicks "Continue Shopping" on the payment page.
 */
class Cancel implements HttpGetActionInterface
{
    public function __construct(
        private ResultFactory $resultFactory,
        private Session $checkoutSession,
        private OrderRepositoryInterface $orderRepository,
        private CartRepositoryInterface $quoteRepository,
        private ResourceConnection $resourceConnection,
        private Config $config
    ) {
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if ($order->getId()
            && $order->getPayment()->getMethod() === Config::CODE
            && $order->getState() === $this->config->getInitialOrderStatus((int)$order->getStoreId())
        ) {
            $connection = $this->resourceConnection->getConnection();

            // Matches only while no payment exists, so a paid order is never closed
            $unpaid = $connection->update(
                $connection->getTableName('ngenius_networkinternational_sales_order'),
                ['state' => 'CANCELLED'],
                ['order_id = ?' => $order->getIncrementId(), "payment_id = ''"]
            );

            if ($unpaid) {
                $storeId = (int)$order->getStoreId();
                $state   = $this->config->getCustomFailedOrderState($storeId) ?: Order::STATE_CLOSED;
                $status  = $this->config->getCustomFailedOrderStatus($storeId) ?: Order::STATE_CLOSED;

                $order->registerCancellation()->setState($state)->setStatus($status);
                $order->addCommentToStatusHistory(__('Customer returned from the payment page without paying.'));
                $this->orderRepository->save($order);

                $quote = $this->quoteRepository->get($order->getQuoteId());
                $quote->setIsActive(1)->setReservedOrderId(null);
                $this->quoteRepository->save($quote);
                $this->checkoutSession->replaceQuote($quote)->unsLastRealOrderId();
            }
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
    }
}
