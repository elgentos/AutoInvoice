<?php

class Elgentos_AutoInvoice_Adminhtml_AutoinvoiceController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Mass create shipments for selected orders
     */
    public function massShipAction()
    {
        if (($cnt = $this->_actionShipOrders($this->getRequest()->getParam('email'))) !== false) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('autoinvoice')->__('Total of %d orders were shipped', (int)$cnt)
            );
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('autoinvoice')->__('Error shipping orders')
            );
        }

        if ($this->getRequest()->getParam('print') && $cnt > 0) {
            return $this->printShipments();
        }

        $this->_redirectReferer();
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

                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('autoinvoice')->__(
                            'Shipment %s for order %s was created.',
                            (int)$shipment->getIncrementId(),
                            (int)$shipment->getOrder()->getIncrementId()
                        )
                    );

                    $ordersShipped++;
                }
            } else {
                $shipments = $order->getShipmentsCollection();
                if($shipments->getSize()) {
                    foreach ($shipments as $shipment) {
                        Mage::getSingleton('adminhtml/session')->addNotice(
                            Mage::helper('autoinvoice')->__(
                                'Could not create shipment for order %s; already has shipment (<a href="%s" target="_blank">%s</a>).',
                                $shipment->getOrder()->getIncrementId(),
                                Mage::helper('adminhtml')->getUrl('*/sales_order_shipment/view', array('shipment_id' => $shipment->getId())),
                                $shipment->getIncrementId()
                            )
                        );
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('autoinvoice')->__(
                            'Could not create shipment for order %s',
                            (int)$order->getIncrementId()
                        )
                    );
                }
            }
        }

        $this->_shipmentIds = array_unique($shipmentIds);

        return $ordersShipped;
    }

    /**
     * Mass print shipments
     * @param  array|string  $shipmentIds comma separated string or array containing shipment ID's
     * @return bool|object      false on failure, PDF objects on success
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

            foreach($shipments as $shipment) {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('autoinvoice')->__(
                        'Shipment %s for order %s was printed.',
                        (int)$shipment->getIncrementId(),
                        (int)$shipment->getOrder()->getIncrementId()
                    )
                );
            }

            return $this->_prepareDownloadResponse('packingslip'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
        } else {
            return false;
        }
    }

    protected function _isAllowed()
    {
        return true;
    }
}
