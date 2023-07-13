<?php

namespace NetworkInternational\NGenius\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\MethodInterface;

/**
 * Class PaymentAction
 */
class PaymentAction implements OptionSourceInterface
{
    public const ACTION_PURCHASE = 'purchased';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => MethodInterface::ACTION_ORDER,
                'label' => __('Order'),
            ]
        ];
    }
}
