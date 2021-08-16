<?php

namespace NetworkInternational\NGenius\Block\Adminhtml;

/**
 * Class Core
 */
class Core extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Core constructor
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_core_report';
        $this->_blockGroup = 'NetworkInternational_NGenius';
        $this->_headerText = __('n-genius Orders');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
