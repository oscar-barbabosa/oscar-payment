<?php
namespace Oscar\Payment\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Add oscar_payment_url field to sales_order table
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'oscar_payment_url',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Oscar Payment URL'
            ]
        );

        // Add oscar_payment_id field to sales_order table
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'oscar_payment_id',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Oscar Payment ID'
            ]
        );

        $setup->endSetup();
    }
} 