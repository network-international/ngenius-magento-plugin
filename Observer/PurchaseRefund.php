<?php

namespace NetworkInternational\NGenius\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use NetworkInternational\NGenius\Controller\NGeniusOnline\Payment;
use Psr\Log\LoggerInterface;
use NetworkInternational\NGenius\Model\CoreFactory;

class PurchaseRefund implements ObserverInterface
{
    /**
     * Common fields in invoice and credit memo
     *
     * @var array|string[]
     */
    private array $fields = [
        'base_currency_code',
        'base_discount_amount',
        'base_discount_tax_compensation_amount',
        'base_grand_total',
        'base_shipping_amount',
        'base_shipping_discount_tax_compensation_amnt',
        'base_shipping_incl_tax',
        'base_shipping_tax_amount',
        'base_subtotal',
        'base_subtotal_incl_tax',
        'base_tax_amount',
        'base_to_global_rate',
        'base_to_order_rate',
        'discount_amount',
        'discount_description',
        'discount_tax_compensation_amount',
        'email_sent',
        'global_currency_code',
        'grand_total',
        'increment_id',
        'invoice_id',
        'order_currency_code',
        'order_id',
        'send_email',
        'shipping_address_id',
        'shipping_amount',
        'shipping_discount_tax_compensation_amount',
        'shipping_incl_tax',
        'shipping_tax_amount',
        'state',
        'store_currency_code',
        'store_id',
        'store_to_base_rate',
        'store_to_order_rate',
        'subtotal',
        'subtotal_incl_tax',
        'tax_amount',
    ];

    /**
     * Common fields in invoice and credit memo items
     *
     * @var array|string[]
     */
    private array $itemFields = [
        'base_price',
        'tax_amount',
        'base_row_total',
        'discount_amount',
        'row_total',
        'base_discount_amount',
        'price_incl_tax',
        'base_tax_amount',
        'base_price_incl_tax',
        'qty',
        'base_cost',
        'price',
        'base_row_total_incl_tax',
        'row_total_incl_tax',
        'discount_tax_compensation_amount',
        'base_discount_tax_compensation_amount',
        'weee_tax_applied_amount',
        'weee_tax_applied_row_amount',
        'weee_tax_disposition',
        'weee_tax_row_disposition',
        'base_weee_tax_applied_amount',
        'base_weee_tax_applied_row_amnt',
        'base_weee_tax_disposition',
        'base_weee_tax_row_disposition',
    ];
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var \NetworkInternational\NGenius\Model\CoreFactory
     */
    private CoreFactory $coreFactory;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \NetworkInternational\NGenius\Model\CoreFactory $coreFactory
     */
    public function __construct(LoggerInterface $logger, CoreFactory $coreFactory)
    {
        $this->logger      = $logger;
        $this->coreFactory = $coreFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $data       = $observer->getData();
        $creditMemo = $data['creditmemo'];
        $order      = $creditMemo->getOrder();

        $payment = $data['payment'];

        $ptid = $payment->getParentTransactionId();
        $collection = $this->coreFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter('payment_id', $ptid);

        $orderItem  = $collection->getFirstItem();
        $reversed = $orderItem->getData('state');

        if ($reversed !== 'REVERSED') {
            return;
        }

        $this->logger->info('Credit Memo: ' . json_encode($creditMemo));
        $invoice = $creditMemo->getInvoice();
        $this->logger->info('Invoice: ' . json_encode($invoice));
        $creditMemo->setGrandTotal($invoice->getGrandTotal());
        $this->setCreditMemoValues($creditMemo, $invoice);
        $this->setInvoiceRefundedValues($creditMemo, $invoice);
        $this->setCreditMemoRefundedQuantities($creditMemo, $invoice);
        $this->setOrderRefundedQuantities($order, $creditMemo, true);
        $order->setStatus(Payment::NGENIUS_VOIDED);
        $order->save();
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return void
     * @throws \Exception
     */
    private function setCreditMemoValues(Creditmemo &$creditMemo, Invoice $invoice): void
    {
        foreach ($this->fields as $field) {
            $creditMemo->setData($field, $invoice->getData($field));
        }

        $creditMemo->save();
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return void
     * @throws \Exception
     */
    private function setInvoiceRefundedValues(Creditmemo $creditMemo, Invoice &$invoice): void
    {
        $invoice->setBaseTotalRefunded($creditMemo->getBaseGrandTotal());

        $invoice->save();
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return void
     */
    private function setCreditMemoRefundedQuantities(Creditmemo &$creditMemo, Invoice $invoice): void
    {
        $creditMemoItems = $creditMemo->getItems();
        $invoiceItems    = $invoice->getItems();

        for ($k = 0; $k < count($creditMemoItems); $k++) {
            $creditMemoItem = $creditMemoItems[$k];
            $creditMemoItem->setCreditMemo($creditMemo);
            $invoiceItem = $invoiceItems->fetchItem();
            if ($creditMemoItem->getQty() == 0) {
                $creditMemoItem->register();
            }
            foreach ($this->itemFields as $field) {
                $creditMemoItem->setData($field, (float)$invoiceItem->getData($field));
            }
            $creditMemoItem->save();
            $creditMemoItems[$k] = $creditMemoItem;
        }

        $creditMemo->setData('items', $creditMemoItems);
        $creditMemo->save();

        $this->setOrderItemValues($creditMemoItems);
    }

    /**
     * @param array $creditMemoItems
     *
     * @return void
     */
    private function setOrderItemValues(array $creditMemoItems): void
    {
        foreach ($creditMemoItems as $creditMemoItem) {
            $orderItem = $creditMemoItem->getOrderItem();
            $orderItem->setQtyRefunded($orderItem->getQtyInvoiced());
            $orderItem->setAmountRefunded($orderItem->getRowInvoiced());
            $orderItem->setBaseAmountRefunded($orderItem->getBaseRowInvoiced());
            $orderItem->setDiscountTaxCompensationRefunded($orderItem->getDiscountTaxCompensationInvoiced());
            $orderItem->setBaseDiscountTaxCompensationRefunded($orderItem->getBaseDiscountTaxCompensationInvoiced());
            $orderItem->setTaxRefunded($orderItem->getTaxInvoiced());
            $orderItem->setBaseTaxRefunded($orderItem->getBaseTaxInvoiced());
            $orderItem->setDiscountRefunded($orderItem->getDiscountInvoiced());
            $orderItem->setBaseDiscountRefunded($orderItem->getBaseDiscountInvoiced());
            $orderItem->save();
        }
    }

    /**
     * @param $order
     * @param $creditMemo
     * @param $online
     *
     * @return void
     */
    private function setOrderRefundedQuantities(&$order, Creditmemo $creditMemo, bool $online = true): void
    {
        $baseOrderRefund = round($creditMemo->getBaseGrandTotal(), 4);
        $orderRefund     = round($creditMemo->getGrandTotal(), 4);

        $order->setBaseTotalRefunded($baseOrderRefund);
        $order->setTotalRefunded($orderRefund);

        $order->setBaseSubtotalRefunded($order->getBaseSubtotalRefunded() + $creditMemo->getBaseSubtotal());
        $order->setSubtotalRefunded($order->getSubtotalRefunded() + $creditMemo->getSubtotal());

        $order->setBaseTaxRefunded($order->getBaseTaxRefunded() + $creditMemo->getBaseTaxAmount());
        $order->setTaxRefunded($order->getTaxRefunded() + $creditMemo->getTaxAmount());
        $order->setBaseDiscountTaxCompensationRefunded(
            $order->getBaseDiscountTaxCompensationRefunded() + $creditMemo->getBaseDiscountTaxCompensationAmount()
        );
        $order->setDiscountTaxCompensationRefunded(
            $order->getDiscountTaxCompensationRefunded() + $creditMemo->getDiscountTaxCompensationAmount()
        );

        $order->setBaseShippingRefunded($order->getBaseShippingRefunded() + $creditMemo->getBaseShippingAmount());
        $order->setShippingRefunded($order->getShippingRefunded() + $creditMemo->getShippingAmount());

        $order->setBaseShippingTaxRefunded(
            $order->getBaseShippingTaxRefunded() + $creditMemo->getBaseShippingTaxAmount()
        );
        $order->setShippingTaxRefunded($order->getShippingTaxRefunded() + $creditMemo->getShippingTaxAmount());

        $order->setAdjustmentPositive($order->getAdjustmentPositive() + $creditMemo->getAdjustmentPositive());
        $order->setBaseAdjustmentPositive(
            $order->getBaseAdjustmentPositive() + $creditMemo->getBaseAdjustmentPositive()
        );

        $order->setAdjustmentNegative($order->getAdjustmentNegative() + $creditMemo->getAdjustmentNegative());
        $order->setBaseAdjustmentNegative(
            $order->getBaseAdjustmentNegative() + $creditMemo->getBaseAdjustmentNegative()
        );

        $order->setDiscountRefunded($order->getDiscountRefunded() + $creditMemo->getDiscountAmount());
        $order->setBaseDiscountRefunded($order->getBaseDiscountRefunded() + $creditMemo->getBaseDiscountAmount());

        if ($online) {
            $order->setTotalOnlineRefunded($order->getTotalOnlineRefunded() + $creditMemo->getGrandTotal());
            $order->setBaseTotalOnlineRefunded(
                $order->getBaseTotalOnlineRefunded() + $creditMemo->getBaseGrandTotal()
            );
        } else {
            $order->setTotalOfflineRefunded($order->getTotalOfflineRefunded() + $creditMemo->getGrandTotal());
            $order->setBaseTotalOfflineRefunded(
                $order->getBaseTotalOfflineRefunded() + $creditMemo->getBaseGrandTotal()
            );
        }

        $order->setBaseTotalInvoicedCost(
            $order->getBaseTotalInvoicedCost() - $creditMemo->getBaseCost()
        );
    }
}
