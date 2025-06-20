<?php

namespace NetworkInternational\NGenius\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Ngenius\NgeniusCommon\NgeniusOrderStatuses;

class DataPatch implements DataPatchInterface
{
    /**
     * N-Genius State
     */
    public const STATE = 'ngenius_state';
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Mark any dependencies here
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Apply Data Patch for NGenius custom order statuses
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $table = $this->moduleDataSetup->getTable('sales_order_status');

        $checkExistingStatus1 = $this->moduleDataSetup->getConnection()->fetchOne(
            $this->moduleDataSetup->getConnection()->select()
                                  ->from($table)
                                  ->where('status = ?', 'ngenius_pending')
        );

        if (!$checkExistingStatus1) {
            $this->insertAllNGeniusStatuses();
        }

        $checkExistingStatus2 = $this->moduleDataSetup->getConnection()->fetchOne(
            $this->moduleDataSetup->getConnection()->select()
                                  ->from($table)
                                  ->where('status = ?', 'ngenius_declined')
        );

        if (!$checkExistingStatus2) {
            $table1 = $this->moduleDataSetup->getTable('sales_order_status');
            if ($this->moduleDataSetup->getConnection()->isTableExists($table1)) {
                $this->moduleDataSetup->getConnection()->insert(
                    $table1,
                    [
                        'status' => 'ngenius_declined',
                        'label'  => 'N-Genius Declined',
                    ]
                );
            }

            $table2 = $this->moduleDataSetup->getTable('sales_order_status_state');
            if ($this->moduleDataSetup->getConnection()->isTableExists($table2)) {
                $this->moduleDataSetup->getConnection()->insert(
                    $table2,
                    [
                        'status'           => 'ngenius_declined',
                        'state'            => 'ngenius_state',
                        'is_default'       => 0,
                        'visible_on_front' => 1,
                    ]
                );
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Installs all custom NGenius Statuses
     *
     * @return void
     */
    private function insertAllNGeniusStatuses(): void
    {
        // Add code from the DataPatch class
        $this->moduleDataSetup->getConnection()
                              ->insertArray(
                                  $this->moduleDataSetup->getTable('sales_order_status'),
                                  ['status', 'label'],
                                  $this->getStatuses()
                              );

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

        $this->moduleDataSetup->getConnection()
                              ->insertArray(
                                  $this->moduleDataSetup->getTable('sales_order_status_state'),
                                  ['status', 'state', 'is_default', 'visible_on_front'],
                                  $state
                              );
    }

    /**
     * Function retrieving all custom NGenius statuses
     *
     * @return string[][]
     */
    public static function getStatuses(): array
    {
        return NgeniusOrderStatuses::magentoOrderStatuses();
    }

    /**
     * For any references to old installation data
     *
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
