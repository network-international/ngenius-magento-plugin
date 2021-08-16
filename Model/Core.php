<?php

namespace NetworkInternational\NGenius\Model;

/**
 * Class Core
 */
class Core extends \Magento\Framework\Model\AbstractModel
{
    /*
     * Initialize
     */

    protected function _construct()
    {
        $this->_init('NetworkInternational\NGenius\Model\ResourceModel\Core');
    }
}
