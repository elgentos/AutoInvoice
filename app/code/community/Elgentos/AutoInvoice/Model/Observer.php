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

            $block->addItem('autoinvoice_massshipment', array(
                'label'   => $helper->__('Create shipments'),
                'url'     => Mage::helper('adminhtml')->getUrl('adminhtml/autoinvoice/massShip'),
                'confirm' => $helper->__('Are you sure?'),
                'additional' => array(
                    'email' => array(
                        'name' => 'email',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => $helper->__('Send email to customer?'),
                        'values' => array(
                            0 => $helper->__('No'),
                            1 => $helper->__('Yes'),
                        ),
                        'value' => 0
                    ),
                    'print' => array(
                        'name' => 'print',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => $helper->__('Print shipments automatically?'),
                        'values' => array(
                            0 => $helper->__('No'),
                            1 => $helper->__('Yes')
                        ),
                        'value' => 1
                    )
                )
            ));
        }
    }
}