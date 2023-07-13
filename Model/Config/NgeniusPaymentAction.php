<?php

namespace NetworkInternational\NGenius\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PaymentAction
 */
class NgeniusPaymentAction implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => "AUTH",
                'label' => __('Authorize'),
            ],
            [
                'value' => "SALE",
                'label' => __('Sale'),
            ],
            [
                'value' => "PURCHASE",
                'label' => __('Purchase'),
            ]
        ];
    }
}
