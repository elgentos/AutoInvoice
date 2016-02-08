<?php

class Elgentos_AutoInvoice_Adminhtml_AutoInvoiceController extends Mage_Adminhtml_Controller_Action {
    /**
     * Create shipments for specified orders
     *
     * @param  array $orderIds  order IDs (array of integers)
     * @param  bool  $sendEmail true to send customer notification
     * @return int              number of shipments created
     */
    protected function _shipOrders($orderIds, $sendEmail) {
        if(!isset($this->_shipmentIds)) {
            $this->_shipmentIds = array();
        }
        $shipmentIds = $this->_shipmentIds;
        $ordersShipped = 0;
        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $orderIds));

        foreach ($orders as $order) {
            if ($order->canShip()) {
                $qtys = array();
                foreach ($order->getAllItems() as $item) {
                    if ($item->canShip()) {
                        $qtys[$item->getId()] = $item->getQtyToShip();
                    }
                }
                $shipment = $order->prepareShipment($qtys);
                if ($shipment) {
                    $shipment->register();
                    if ($sendEmail) {
                        $shipment->setEmailSent(true);
                    }
                    $shipment->getOrder()->setIsInProcess(true);
                    Mage::getModel('core/resource_transaction')
                        ->addObject($shipment)
                        ->addObject($shipment->getOrder())
                        ->save();

                    $shipmentIds[] = $shipment->getId();
                    if ($sendEmail) {
                        $shipment->sendEmail();
                    }
                    $ordersShipped++;
                }
            }
        }

        $this->_shipmentIds = array_unique($shipmentIds);

        return $ordersShipped;
    }

    /**
     * Create shipments for orders specified by "order_ids" param
     *
     * @param  boolean  $sendEmail true to send customer notification
     * @return bool|int            number of shipments created or false
     */
    protected function _actionShipOrders($sendEmail = false) {
        $ids = $this->getRequest()->getParam('order_ids');
        try {
            return $this->_shipOrders($ids, $sendEmail);
        } catch (Exception $e) {
            return false;
        }
    }

    public function massShipAction() {
        /*if($this->getRequest()->getParam('email')) {
            $this->massShipEmail();
        } else {
            $this->massShipNoEmail();
        }*/

        if($this->getRequest()->getParam('print')) {
            $this->printShipments();
        }
    }

    /**
     * Mass print shipments
     */
    public function printShipments($shipmentIds = null) {
        if(is_null($shipmentIds) && isset($this->_shipmentIds) && is_array($this->_shipmentIds)) {
            $shipmentIds = $this->_shipmentIds;
        } elseif(is_string($shipmentIds) && stripos($shipmentIds, ',') !== false) {
            $shipmentIds = array_map('trim', explode(',', $shipmentIds));
        } elseif($this->getRequest()->getParam('order_ids')) {
            $orderIds = $this->getRequest()->getParam('order_ids');
        }

        if (!empty($shipmentIds)) {
            $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $shipmentIds))
                ->load();
        } elseif (!empty($orderIds)) {
            $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('order_id', array('in' => $orderIds))
                ->load();
        }

        if(isset($shipments)) {
            if (!isset($pdf)){
                $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
            } else {
                $pages = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
                $pdf->pages = array_merge ($pdf->pages, $pages->pages);
            }

            return $this->_prepareDownloadResponse('packingslip'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
        } else {
            return false;
        }
    }

    /**
     * Mass shipment action (no emails)
     */
    public function massShipNoEmail() {
        if (($cnt = $this->_actionShipOrders()) !== false) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('autoinvoice')->__('Total of %d orders were shipped', (int) $cnt)
            );
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('autoinvoice')->__('Error shipping orders')
            );
        }
        $this->_redirect('adminhtml/sales_order/index');
    }

    /**
     * Mass shipment action (with emails)
     */
    public function massShipEmail() {
        if (($cnt = $this->_actionShipOrders(true)) !== false) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('autoinvoice')->__('Total of %d orders were shipped (emails sent)', (int) $cnt)
            );
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('autoinvoice')->__('Error shipping orders')
            );
        }
        $this->_redirect('adminhtml/sales_order/index');
    }

    protected function _isAllowed()
    {
        return true;
    }
}