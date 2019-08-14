<?php

class Koan_Seg_Adminhtml_SegController extends Mage_Adminhtml_Controller_Action
{
    private function _checkIfCronActivated()
    {

        $isCronActive = Mage::helper('koan_seg')->isExportCronEnabled();
        if (!$isCronActive) {

            Mage::getSingleton('adminhtml/session')->addWarning($this->__('Exporter CRON operation is set to INACTIVE in config and export will not work.
                Please navigate to System -> Configuration -> Koan -> Seg Options and set "Enable export CRON value to YES"'));
        }

        return $this;

    }

    public function exporterAction()
    {
        $this->_checkIfCronActivated();

        $this->loadLayout();
        $this->_setActiveMenu('seg');
        $this->renderLayout();
    }

    public function createBatchCustomerAction()
    {
        $exporter = Mage::getSingleton('koan_seg/seg_exporter');

        $batchCollection = Mage::getResourceModel('koan_seg/batch_status_collection');
        $batchCollection->addFieldToFilter('entity_type', 'customers');
        $batchCollection->addFieldToFilter('current_status',
            array('nin' => array(Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_COMPLETE, Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_ERROR)
            ));

        try {

            $isRunning = $batchCollection->getSize();
            if ($isRunning) {
                Mage::throwException('Can not start new batch while one is still running. Please try again later!');
                return;
            }

            $exporter->generateCustomersExportBatch();
            $msg = Mage::helper('koan_seg')->__('New batch has been scheduled successfully. Export will start with new CRON job in several minutes.');

            Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('adminhtml/seg/exporter');
        return;
    }

    public function createBatchOrderAction()
    {
        $orderDateFilter = null;

        $fromDateFiter = $this->getRequest()->getParam('order_date_from');
        if ($fromDateFiter AND $fromDateFiter != 'NO_FILTER') {
            $filter = @json_decode($fromDateFiter, true);
            if ($filter AND is_array($filter) AND isset($filter['date'])) {
                $orderDateFilter = $filter['date'];
            }
        }

        $exporter = Mage::getSingleton('koan_seg/seg_exporter');

        $batchCollection = Mage::getResourceModel('koan_seg/batch_status_collection');
        $batchCollection->addFieldToFilter('entity_type', 'history_orders');
        $batchCollection->addFieldToFilter('current_status',
            array('nin' => array(Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_COMPLETE, Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_ERROR)
            ));

        try {

            $isRunning = $batchCollection->getSize();
            if ($isRunning) {
                Mage::throwException('Can not start new batch while one is still running. Please try again later!');
                return;
            }

            $exporter->generateHistoryOrdersExportBatch($orderDateFilter);
            $msg = Mage::helper('koan_seg')->__('New batch has been scheduled successfully. Export will start with new CRON job in several minutes.');

            Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('adminhtml/seg/exporter');
        return;
    }

    public function massDeleteAction()
    {
        $batchIds = $this->getRequest()->getParam('batch_id', null);
        if (is_null($batchIds) OR !is_array($batchIds) OR count($batchIds) < 1) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select batch(es)'));
        } else {
            try {
                foreach ($batchIds as $batchId) {
                    $batch = Mage::getModel('koan_seg/batch_status')->load($batchId);
                    if ($batch AND $batch->getId()) {
                        $batch->delete();
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d rows(s) were successfully deleted', count($batchIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('adminhtml/seg/exporter');
    }

}