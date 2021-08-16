<?php

namespace NetworkInternational\NGenius\Model\ResourceModel;

/**
 * Class Core
 */
class Core extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Core constructor.
     *
     * @param  \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    /*
     * Initialize
     */

    protected function _construct()
    {
        $this->_init('ngenius_networkinternational', 'nid');
    }
}
