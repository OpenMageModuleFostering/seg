<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('koan_seg/batch_status'),
    'website_id',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'nullable' => true,
        'default' => null,
        'comment' => 'Website Id'
    )
);
$installer->endSetup();