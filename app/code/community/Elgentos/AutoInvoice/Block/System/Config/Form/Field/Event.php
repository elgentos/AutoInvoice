<?php

class Elgentos_AutoInvoice_Block_System_Config_Form_Field_Event extends Mage_Core_Block_Html_Select
{
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        $statuses = array(
            'sales_order_place_after',
            'sales_order_save_after',
            'sales_order_payment_pay'
        );

        foreach ($statuses as $value) {
            $this->addOption($value, $value);
        }

        return parent::_toHtml();
    }
}
