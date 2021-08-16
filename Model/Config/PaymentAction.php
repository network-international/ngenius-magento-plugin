<?php

namespace NetworkInternational\NGenius\Model\Config;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
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
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Sale'),
            ]
        ];
    }
}
