<?php

namespace NetworkInternational\NGenius\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\MethodInterface;

/**
 * Magento2 payment action model
 *
 * Class PaymentAction
 */
class PaymentAction implements OptionSourceInterface
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
                'value' => MethodInterface::ACTION_AUTHORIZE,
                'label' => __('Authorize'),
            ]
        ];
    }
}
