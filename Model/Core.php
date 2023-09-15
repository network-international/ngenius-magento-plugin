<?php

namespace NetworkInternational\NGenius\Model;

use Magento\Framework\Model\AbstractModel;
use NetworkInternational\NGenius\Model\ResourceModel\Core as ResourceCore;

/**
 * Resource model core initializer
 *
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
        $this->_init(ResourceCore::class);
    }
}
