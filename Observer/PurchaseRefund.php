<?php

namespace NetworkInternational\NGenius\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order;
use NetworkInternational\NGenius\Controller\NGeniusOnline\Payment;
use Psr\Log\LoggerInterface;
use NetworkInternational\NGenius\Model\CoreFactory;
use NetworkInternational\NGenius\Gateway\Config\Config;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var CoreFactory
     */
    private CoreFactory $coreFactory;
    /**
     * @var Config
     */
    private Config $config;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param LoggerInterface $logger
     * @param CoreFactory $coreFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        CoreFactory $coreFactory,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->logger        = $logger;
        $this->coreFactory   = $coreFactory;
        $this->config        = $config;
        $this->storeManager  = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $data       = $observer->getData();
        $creditMemo = $data['creditmemo'];
        $order      = $creditMemo->getOrder();

        if ($order->getPayment()->getMethodInstance()->getCode() !== Config::CODE) {
            return;
        }

        $payment = $data['payment'];

        $parentTransactionId = $payment->getParentTransactionId() !== null ? $payment->getParentTransactionId() : '';

        $ptid = str_replace("-capture", "", $parentTransactionId);
        $collection = $this->coreFactory->create()
            ->getCollection()
            ->addFieldToFilter('payment_id', $ptid);

        $orderItem  = $collection->getFirstItem();
        $itemStatus = $orderItem->getData('status') ?? "";
        $itemState = $orderItem->getData('state') ?? "";

        $storeId = $this->storeManager->getStore()->getId();

        if ($itemState === 'REVERSED') {
            $this->logger->info('Credit Memo: ' . json_encode($creditMemo));
            $invoice = $creditMemo->getInvoice();
            $this->logger->info('Invoice: ' . json_encode($invoice));
            $creditMemo->setGrandTotal($invoice->getGrandTotal());
            $this->setCreditMemoValues($creditMemo, $invoice);
            $this->setInvoiceRefundedValues($creditMemo, $invoice);
            $this->setCreditMemoRefundedQuantities($creditMemo, $invoice);
            $this->setOrderRefundedQuantities($order, $creditMemo);
            $order->setStatus(Payment::NGENIUS_VOIDED);
        } elseif ($this->config->getIsNgeniusRefundStatus($storeId)) {
            $order->setState($itemState);
            $order->setStatus($itemStatus);
        }
        $order->save();
    }

    /**
     * Updates credit memo for order
     *
     * @param Creditmemo $creditMemo
     * @param Invoice $invoice
     *
     * @return void
     * @throws Exception
     */
    private function setCreditMemoValues(Creditmemo &$creditMemo, Invoice $invoice): void
    {
        foreach ($this->fields as $field) {
            $creditMemo->setData($field, $invoice->getData($field));
        }

        $creditMemo->save();
    }

    /**
     * Updates refund data for invoice
     *
     * @param Creditmemo $creditMemo
     * @param Invoice $invoice
     *
     * @return void
     * @throws Exception
     */
    private function setInvoiceRefundedValues(Creditmemo $creditMemo, Invoice &$invoice): void
    {
        $invoice->setBaseTotalRefunded($creditMemo->getBaseGrandTotal());

        $invoice->save();
    }

    /**
     * Updates credit memo refund item data
     *
     * @param Creditmemo $creditMemo
     * @param Invoice $invoice
     *
     * @return void
     * @throws Exception
     */
    private function setCreditMemoRefundedQuantities(Creditmemo &$creditMemo, Invoice $invoice): void
    {
        $creditMemoItems = $creditMemo->getItems();
        $invoiceItems    = $invoice->getItems();

        $creditMemoItemCount = count($creditMemoItems);

        for ($k = 0; $k < $creditMemoItemCount; $k++) {
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
     * Updates the order items(s)
     *
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
     * Sets order refund quantities
     *
     * @param Order $order
     * @param Creditmemo $creditMemo
     *
     * @return void
     */
    private function setOrderRefundedQuantities(Order &$order, Creditmemo $creditMemo): void
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

        $order->setTotalOnlineRefunded($order->getTotalOnlineRefunded() + $creditMemo->getGrandTotal());
        $order->setBaseTotalOnlineRefunded(
            $order->getBaseTotalOnlineRefunded() + $creditMemo->getBaseGrandTotal()
        );

        $order->setBaseTotalInvoicedCost(
            $order->getBaseTotalInvoicedCost() - $creditMemo->getBaseCost()
        );
    }
}
