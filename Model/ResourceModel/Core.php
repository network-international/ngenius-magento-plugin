<?php

namespace NetworkInternational\NGenius\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for custom NGenius order DB
 *
 * Class Core
 */
class Core extends AbstractDb
{
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ngenius_networkinternational_sales_order', 'nid');
    }
}
