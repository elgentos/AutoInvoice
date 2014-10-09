<?php

class Elgentos_AutoInvoice_Block_System_Config_Form_Field_Orderstatus extends Mage_Core_Block_Html_Select {
    public function setInputName($value) {
        return $this->setName($value);
    }

    public function _toHtml() {
        $statuses = Mage::getModel('sales/order_config')->getStatuses();
        foreach ($statuses as $value => $label) {
            $this->addOption($value, $label);
        }
        return parent::_toHtml();
    }
}
