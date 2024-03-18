<?php

namespace NetworkInternational\NGenius\Model\Config;

use Magento\Framework\Option\ArrayInterface;
use NetworkInternational\NGenius\Setup\Patch\Data\DataPatch;

/**
 * NGenius order statuses model
 *
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
        $status = DataPatch::getStatuses();

        return [['value' => $status[0]['status'], 'label' => __($status[0]['label'])]];
    }
}
