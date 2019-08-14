<?php

class Koan_Seg_Block_Adminhtml_Exporter extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    private function _getOrderBatchUrl()
    {
        $orderBatchUrl = Mage::getModel('adminhtml/url')->getUrl(
            'adminhtml/seg/createBatchOrder',
            array('order_date_from' => 'NO_FILTER')
        );

        return $orderBatchUrl;
    }

    public function __construct()
    {
        $this->_controller = 'adminhtml_exporter';
        $this->_blockGroup = 'koan_seg';
        $this->_headerText = Mage::helper('koan_seg')->__('Seg Exporter');
        $this->_addButtonLabel = Mage::helper('koan_seg')->__('Export');
        parent::__construct();
        $this->_removeButton('add');

        $customerUrl = Mage::getModel('adminhtml/url')->getUrl(
            'adminhtml/seg/createBatchCustomer',
            null
        );


        $this->addButton(
            'start_order_batch',
            array(
                'label' => Mage::helper('koan_seg')->__('Start New Orders Export Batch'),
                'onclick' => 'getOrderBatchUrl()',
                'class' => 'add',
                'before_html' => $this->_getDateSelectorHtml()
            )
        );

        $this->addButton(
            'start_customer_batch',
            array(
                'label' => Mage::helper('koan_seg')->__('Start New Customers Export Batch'),
                'onclick' => 'setLocation(\'' . $customerUrl . '\')',
                'class' => 'add',
                'style' => 'margin-right:20px;'
            )
        );
    }

    private function _getDateSelectorHtml()
    {
        $element = new Varien_Data_Form_Element_Date(
            array(
                'name' => 'order_date_filter',
                'label' => Mage::helper('koan_seg')->__('Date'),
                'tabindex' => 1,
                'image' => $this->getSkinUrl('images/grid-cal.gif'),
                'format' => Varien_Date::DATE_INTERNAL_FORMAT,
                'value' => null
            )
        );

        $element->setForm(new Varien_Data_Form());
        $element->setId('order_date_filter');

        return '<label for="order_date_filter">Filter orders from date: </label>' . $element->getElementHtml();
    }

    private function _getDateFilterJs()
    {
        $js = '<script>';
        $js .= 'function getOrderBatchUrl(){';
        $js .= 'var url=\'' . $this->_getOrderBatchUrl() . '\';';
        $js .= 'var dtFilter = $("order_date_filter").getValue();';
        $js .= 'if(dtFilter){url = url.sub("NO_FILTER", JSON.stringify({"date":dtFilter}));}';
        //$js .= 'if(dtFilter){url = url.sub("NO_FILTER", dtFilter);}';
        $js .= 'setLocation(url);';
        $js .= '}';
        $js .= '</script>';

        return $js;
    }

    protected function _toHtml()
    {
        $html = parent::_toHtml();
        $html .= $this->_getDateFilterJs();

        return $html;
    }


}