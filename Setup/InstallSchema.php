<?php

namespace NetworkInternational\NGenius\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Install
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return null
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();

        $table = $setup->getConnection()->newTable(
            $setup->getTable('ngenius_networkinternational')
        )->addColumn(
            'nid',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'n-genius Id'
        )->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Entity Id'
        )->addColumn(
            'order_id',
            Table::TYPE_TEXT,
            55,
            ['unsigned' => true, 'nullable' => false],
            'Order Id'
        )->addColumn(
            'amount',
            Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true, 'nullable' => false],
            'Amount'
        )->addColumn(
            'currency',
            Table::TYPE_TEXT,
            3,
            ['unsigned' => true, 'nullable' => false],
            'Currency'
        )->addColumn(
            'reference',
            Table::TYPE_TEXT,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Reference'
        )->addColumn(
            'action',
            Table::TYPE_TEXT,
            20,
            ['unsigned' => true, 'nullable' => false],
            'Action'
        )->addColumn(
            'state',
            Table::TYPE_TEXT,
            20,
            ['unsigned' => true, 'nullable' => false],
            'State'
        )->addColumn(
            'status',
            Table::TYPE_TEXT,
            50,
            ['unsigned' => true, 'nullable' => false],
            'Status'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 'CURRENT_TIMESTAMP'],
            'Created At'
        )->addColumn(
            'payment_id',
            Table::TYPE_TEXT,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Payment Id'
        )->addColumn(
            'captured_amt',
            Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true, 'nullable' => false],
            'Captured Amount'
        )->addIndex(
            $setup->getIdxName(
                'ngenius_online',
                ['entity_id', 'order_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['entity_id', 'order_id'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )->setComment('N-Genius order table')
                ->setOption('charset', 'utf8');

        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}
