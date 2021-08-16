<?php

namespace NetworkInternational\NGenius\Model\Config;

/**
 * Class Environment
 */
class Tenant implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'networkinternational', 'label' => __('Network International')]];
    }
}
