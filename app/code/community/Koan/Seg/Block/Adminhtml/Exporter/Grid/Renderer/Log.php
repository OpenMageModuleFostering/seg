<?php

class Koan_Seg_Block_Adminhtml_Exporter_Grid_Renderer_Log extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $id = $row->getId();

        $url = $this->getUrl('*/seg/log', array('id' => $id));

        $action = "popWin(this.href,'_blank','width=1024,height=700,resizable=1,scrollbars=1');return false;";

        $html = sprintf('<a onclick="%s" href="%s" target="_blank">%s</a>',$action, $url, $this->__('View Log'));
        return $html;

    }
}