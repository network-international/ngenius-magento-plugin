<?php

namespace NetworkInternational\NGenius\Gateway\Request;

/**
 * Class SaleRequest
 */
class SaleRequest extends AbstractRequest
{

    /**
     * Gets array of data for API request
     *
     * @param object $order
     * @param int $storeId
     * @param float $amount
     * @return array
     */
    public function getBuildArray($order, $storeId, $amount)
    {

        return[
            'data' => [
                'action' => 'SALE',
                'amount' => [
                    'currencyCode' => $order->getCurrencyCode(),
                    'value' => $amount
                ],
                'merchantAttributes' => [
                    'redirectUrl' => $this->urlBuilder->getDirectUrl("networkinternational/ngeniusonline/payment"),
                    'skipConfirmationPage' => true,
                ],
                'merchantOrderReference' => $order->getOrderIncrementId(),
                'emailAddress'           => $order->getBillingAddress()->getEmail(),
                'billingAddress' => [
                    'firstName' => $order->getBillingAddress()->getFirstName(),
                    'lastName' => $order->getBillingAddress()->getLastName(),
                ]
            ],
            'method' => \Zend_Http_Client::POST,
            'uri' => $this->config->getOrderRequestURL($storeId)
        ];
    }
}
