<?php

class Koan_Seg_Block_Adminhtml_Exporter_Grid_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    private function getStatusText($status)
    {
        $statuses = array(
            Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_NOT_STARTED => 'Export not started yet',
            Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_STARTING => 'Export starting in progress',
            Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_PROCESSING_ROWS => 'Processing rows',
            Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_COMPLETE => 'Export completed',
            Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_ERROR => 'Error',
            Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_NEED_RETRY => 'Pending Retry'

        );

        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        return $this->getStatusText($value);

    }
}