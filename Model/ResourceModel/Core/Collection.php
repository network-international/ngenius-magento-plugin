<?php

namespace NetworkInternational\NGenius\Model\ResourceModel\Core;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use NetworkInternational\NGenius\Model\Core as NGeniusModelCore;
use NetworkInternational\NGenius\Model\ResourceModel\Core as NGeniusModelResourceCore;

/**
 * Model resource collection for Custom NGenius order table
 *
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
            NGeniusModelCore::class,
            NGeniusModelResourceCore::class
        );
    }
}
