<?php

namespace NetworkInternational\NGenius\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Ngenius\NgeniusCommon\NgeniusOrderStatuses;

/**
 * Class InstallData
 */
class InstallData implements InstallDataInterface
{
    /**
     * N-Genius State
     */
    public const STATE = 'ngenius_state';

    /**
     * Install
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return null
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $setup->getConnection()
              ->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $this->getStatuses());

        $state[] = ['ngenius_pending', self::STATE, '1', '1'];
        $state[] = ['ngenius_processing', self::STATE, '0', '1'];
        $state[] = ['ngenius_failed', self::STATE, '0', '1'];
        $state[] = ['ngenius_complete', self::STATE, '0', '1'];
        $state[] = ['ngenius_authorised', self::STATE, '0', '1'];
        $state[] = ['ngenius_fully_captured', self::STATE, '0', '1'];
        $state[] = ['ngenius_partially_captured', self::STATE, '0', '1'];
        $state[] = ['ngenius_fully_refunded', self::STATE, '0', '1'];
        $state[] = ['ngenius_partially_refunded', self::STATE, '0', '1'];
        $state[] = ['ngenius_auth_reversed', self::STATE, '0', '1'];
        $state[] = ['ngenius_declined', self::STATE, '0', '1'];

        $setup->getConnection()
              ->insertArray(
                  $setup->getTable('sales_order_status_state'),
                  ['status', 'state', 'is_default', 'visible_on_front'],
                  $state
              );

        $setup->endSetup();
    }

    /**
     * @return \string[][]
     */
    public static function getStatuses(): array
    {
        return NgeniusOrderStatuses::magentoOrderStatuses();
    }
}
