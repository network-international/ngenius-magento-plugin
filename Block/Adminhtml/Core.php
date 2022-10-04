<?php

namespace NetworkInternational\NGenius\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Core
 */
class Core extends Container
{
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * Core constructor
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_core_report';
        $this->_blockGroup = 'NetworkInternational_NGenius';
        $this->_headerText = __('N-Genius Orders');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
