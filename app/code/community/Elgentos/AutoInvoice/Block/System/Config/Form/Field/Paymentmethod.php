<?php

class Elgentos_AutoInvoice_Block_System_Config_Form_Field_Paymentmethod extends Mage_Core_Block_Html_Select
{
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function _toHtml()
    {
        $payments = Mage::getModel('payment/config')->getActiveMethods();
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/' . $paymentCode . '/title');
            $this->addOption($paymentCode, $paymentTitle);
        }
        return parent::_toHtml();
    }
}
