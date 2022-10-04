<?php

namespace NetworkInternational\NGenius\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

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
        return [
            ['status' => 'ngenius_pending', 'label' => 'N-Genius Pending'],
            ['status' => 'ngenius_processing', 'label' => 'N-Genius Processing'],
            ['status' => 'ngenius_failed', 'label' => 'N-Genius Failed'],
            ['status' => 'ngenius_complete', 'label' => 'N-Genius Complete'],
            ['status' => 'ngenius_authorised', 'label' => 'N-Genius Authorised'],
            ['status' => 'ngenius_fully_captured', 'label' => 'N-Genius Fully Captured'],
            ['status' => 'ngenius_partially_captured', 'label' => 'N-Genius Partially Captured'],
            ['status' => 'ngenius_fully_refunded', 'label' => 'N-Genius Fully Refunded'],
            ['status' => 'ngenius_partially_refunded', 'label' => 'N-Genius Partially Refunded'],
            ['status' => 'ngenius_auth_reversed', 'label' => 'N-Genius Auth Reversed'],
            ['status' => 'ngenius_declined', 'label' => 'N-Genius Declined'],
        ];
    }
}
