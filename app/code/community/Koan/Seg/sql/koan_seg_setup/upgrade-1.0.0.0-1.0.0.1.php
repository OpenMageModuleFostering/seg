<?php

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('koan_seg/batch_status'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true
    ))
    ->addColumn('entity_type', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        'nullable' => false
    ))
    ->addColumn('start_time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Start Time')
    ->addColumn('end_time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'End Time')
    ->addColumn('total_row_count', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false
    ))
    ->addColumn('num_rows_processed', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false
    ))
    ->addColumn('current_status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'default' => '0'
    ))
    ->addColumn('comment', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable' => false
    ))
    ->addColumn('num_retried', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false
    ));
$installer->getConnection()->createTable($table);
