<?php

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('koan_seg/batch_log'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true
    ))
    ->addColumn('batch_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false
    ))
    ->addColumn('start', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false
    ))
    ->addColumn('limit', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false
    ))
    ->addColumn('comment', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable' => false
    ))
    ->addColumn('num_retried', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false
    ));
$installer->getConnection()->createTable($table);
$installer->endSetup();