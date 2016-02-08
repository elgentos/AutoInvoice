<?php

class Elgentos_AutoInvoice_Model_Observer {
    /**
     * Add new mass actions to Orders grid
     *
     * @param Varien_Event_Observer $observer
     */
    public function addMassActionShipment(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();

        if (
            $block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction
            && $block->getRequest()->getControllerName() == 'sales_order'
        ) {
            $helper = Mage::helper('autoinvoice');

            $block->addItem('autoinvoice_massshipment_no_emails_no_ship', array(
                'label'   => $helper->__('Ship (no emails)'),
                'url'     => Mage::helper('adminhtml')->getUrl('adminhtml/autoinvoice/massShip', array('email' => false, 'print' => false)),
                'confirm' => $helper->__('Are you sure?'),
            ));

            $block->addItem('autoinvoice_massshipment_with_emails_no_ship', array(
                'label'   => $helper->__('Ship (with emails)'),
                'url'     => Mage::helper('adminhtml')->getUrl('adminhtml/autoinvoice/massShip', array('email' => true, 'print' => false)),
                'confirm' => $helper->__('Are you sure?'),
            ));

            $block->addItem('autoinvoice_massshipment_no_emails', array(
                'label'   => $helper->__('Ship & print (no emails)'),
                'url'     => Mage::helper('adminhtml')->getUrl('adminhtml/autoinvoice/massShip', array('email' => false, 'print' => true)),
                'confirm' => $helper->__('Are you sure?'),
            ));

            $block->addItem('autoinvoice_massshipment_with_emails', array(
                'label'   => $helper->__('Ship & print (with emails)'),
                'url'     => Mage::helper('adminhtml')->getUrl('adminhtml/autoinvoice/massShip', array('email' => true, 'print' => true)),
                'confirm' => $helper->__('Are you sure?'),
            ));
        }
    }
}