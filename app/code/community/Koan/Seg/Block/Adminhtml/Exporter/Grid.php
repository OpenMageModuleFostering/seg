<?php

class Koan_Seg_Block_Adminhtml_Exporter_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();

        $this->setId('segExporterGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('koan_seg/batch_status_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header' => Mage::helper('koan_seg')->__('Id'),
            'align' => 'right',
            'width' => '40px',
            'index' => 'id',
        ));

        $this->addColumn('entity_type', array(
            'header' => Mage::helper('koan_seg')->__('Entity Type'),
            'align' => 'left',
            'index' => 'entity_type',
        ));

        $this->addColumn('start_time', array(
            'header' => Mage::helper('koan_seg')->__('Start Time'),
            'align' => 'left',
            'index' => 'start_time',
        ));

        $this->addColumn('end_time', array(
            'header' => Mage::helper('koan_seg')->__('End Time'),
            'align' => 'left',
            'index' => 'end_time',
        ));
        $this->addColumn('total_row_count', array(
            'header' => Mage::helper('koan_seg')->__('Total Items Count'),
            'align' => 'left',
            'index' => 'total_row_count',
        ));
        $this->addColumn('num_rows_processed', array(
            'header' => Mage::helper('koan_seg')->__('Items Processed'),
            'align' => 'left',
            'index' => 'num_rows_processed',
        ));

        $this->addColumn('current_status', array(
            'header' => Mage::helper('koan_seg')->__('Current Status'),
            'align' => 'left',
            'index' => 'current_status',
            'renderer' => 'koan_seg/adminhtml_exporter_grid_renderer_status',
        ));
        $this->addColumn('comment', array(
            'header' => Mage::helper('koan_seg')->__('Comment'),
            'align' => 'left',
            'index' => 'comment',
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('batch_id');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('koan_seg')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('koan_seg')->__('Are you sure?')
        ));

        return $this;
    }
}