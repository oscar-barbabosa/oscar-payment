<?php
namespace Oscar\Payment\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallData implements InstallDataInterface
{
    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Add oscar_payment_url field to quote table
        $setup->getConnection()->addColumn(
            $setup->getTable('quote'),
            'oscar_payment_url',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Oscar Payment URL'
            ]
        );

        // Add oscar_payment_id field to quote table
        $setup->getConnection()->addColumn(
            $setup->getTable('quote'),
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