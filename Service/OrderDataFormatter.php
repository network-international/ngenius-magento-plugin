<?php

namespace NetworkInternational\NGenius\Service;

use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use NetworkInternational\NGenius\Gateway\Config\Config;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;

class OrderDataFormatter
{
    private UrlInterface $urlBuilder;
    private Config $config;

    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(UrlInterface $urlBuilder, Config $config)
    {
        $this->urlBuilder = $urlBuilder;
        $this->config     = $config;
    }

    public function format(Order $order): array
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = [
                'description' => $item->getQtyOrdered() . ' x ' . $item->getName(),
                'totalPrice'  => [
                    'currencyCode' => $order->getOrderCurrencyCode(),
                    'value'        => (int)($item->getRowTotal() * 100)
                ],
                'quantity'    => (int)$item->getQtyOrdered(),
            ];
        }

        $redirectUrl = $this->urlBuilder->getDirectUrl(
            "networkinternational/ngeniusonline/payment"
        );

        $orderStatusHistories = $order->getStatusHistories();

        return [
            'firstName'              => $order->getCustomerFirstname(),
            'lastName'               => $order->getCustomerLastname(),
            'email'                  => $order->getCustomerEmail(),
            'emailSubject'           => 'Pay for your order',
            'transactionType'        => $this->config->getPaymentAction($order->getStoreId()),
            'total'                  => [
                'currencyCode' => $order->getOrderCurrencyCode(),
                'value'        => ValueFormatter::floatToIntRepresentation(
                    $order->getOrderCurrencyCode(),
                    $order->getGrandTotal()
                )
            ],
            'invoiceExpiryDate'      => date('Y-m-d\TH:i:s', strtotime('+3 days')),
            'merchantOrderReference' => $order->getIncrementId(),
            'message'                => $orderStatusHistories && count($orderStatusHistories) > 1
                ? $orderStatusHistories[count($orderStatusHistories) - 2]->getComment()
                : 'Thank you for your order',
            'items'                  => $items,
            'redirectUrl'            => $redirectUrl
        ];
    }
}
