<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addForeignKey(
    $installer->getFkName('koan_seg/batch_log', 'batch_id', 'koan_seg/batch_status', 'id'),
    $installer->getTable('koan_seg/batch_log'),
    'batch_id',
    $installer->getTable('koan_seg/batch_status'),
    'id'
);

$installer->endSetup();