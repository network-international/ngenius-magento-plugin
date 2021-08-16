<?php

namespace NetworkInternational\NGenius\Model\Config;

/**
 * Class Environment
 */
class Environment implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'uat', 'label' => __('UAT')], ['value' => 'live', 'label' => __('Live')]];
    }
}
