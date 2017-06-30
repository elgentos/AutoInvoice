<?php

class Elgentos_AutoInvoice_Block_System_Config_Form_Field_Paymentmethod extends Mage_Core_Block_Html_Select
{
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function _toHtml()
    {
        $payments = Mage::helper('payment')->getPaymentMethods();
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/' . $paymentCode . '/title');
            if ($paymentTitle) {
                $this->addOption($paymentCode, $paymentTitle . ' (' . $paymentCode . ')');
            }
        }
        $options = $this->getOptions();
        usort($options, function($a, $b){
            return strcasecmp($a['label'], $b['label']);
        });
        $this->setOptions($options);
        return parent::_toHtml();
    }
}
