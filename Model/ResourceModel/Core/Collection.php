<?php

namespace NetworkInternational\NGenius\Model\ResourceModel\Core;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize
     *
     * @phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
     */
    protected function _construct()
    {
        $this->_init(
            'NetworkInternational\NGenius\Model\Core',
            'NetworkInternational\NGenius\Model\ResourceModel\Core'
        );
    }
}
