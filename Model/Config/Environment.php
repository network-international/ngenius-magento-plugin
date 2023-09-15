<?php

namespace NetworkInternational\NGenius\Model\Config;

use Magento\Framework\Option\ArrayInterface;

/**
 * Pay environment setting model
 *
 * Class Environment
 */
class Environment implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'uat', 'label' => __('Sandbox')], ['value' => 'live', 'label' => __('Live')]];
    }
}
