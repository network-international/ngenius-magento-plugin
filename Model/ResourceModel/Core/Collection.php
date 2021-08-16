<?php

namespace NetworkInternational\NGenius\Model\ResourceModel\Core;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /*
     * Initialize
     */

    protected function _construct()
    {
        $this->_init('NetworkInternational\NGenius\Model\Core', 'NetworkInternational\NGenius\Model\ResourceModel\Core');
    }
}
