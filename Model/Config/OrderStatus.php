<?php

namespace NetworkInternational\NGenius\Model\Config;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class OrderStatus
 */
class OrderStatus implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {

        $status = \NetworkInternational\NGenius\Setup\InstallData::getStatuses();

        return [['value' => $status[0]['status'], 'label' => __($status[0]['label'])]];
    }
}
