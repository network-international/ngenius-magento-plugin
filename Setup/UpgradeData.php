<?php

namespace NetworkInternational\NGenius\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{

    /**
     * @inheritDoc
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') === 0) {
            $table1 = $setup->getTable('sales_order_status');
            if ($setup->getConnection()->isTableExists($table1)) {
                $setup->getConnection()->insert($table1, [
                    'status' => 'ngenius_declined',
                    'label'  => 'N-Genius Declined',
                ]);
            }

            $table2 = $setup->getTable('sales_order_status_state');
            if ($setup->getConnection()->isTableExists($table2)) {
                $setup->getConnection()->insert($table2, [
                    'status'           => 'ngenius_declined',
                    'state'            => 'ngenius_state',
                    'is_default'       => 0,
                    'visible_on_front' => 1,
                ]);
            }
        }

        $setup->endSetup();
    }
}
