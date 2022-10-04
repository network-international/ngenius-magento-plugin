<?php

namespace NetworkInternational\NGenius\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Core
 */
class Core extends AbstractModel
{
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

    /**
     * Initialize
     */
    protected function _construct()
    {
        $this->_init('NetworkInternational\NGenius\Model\ResourceModel\Core');
    }
}
