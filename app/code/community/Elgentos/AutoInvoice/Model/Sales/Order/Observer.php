<?php

class Elgentos_AutoInvoice_Model_Sales_Order_Observer {
    function place_after($observer) {
        $order = $observer->getOrder();
        $processConditions = unserialize(Mage::getStoreConfig('autoinvoice/general/conditions_to_process'));

        $state = $order->getState();
        $status = $order->getStatus();
        $paymentMethod = $order->getPayment()->getMethod();

        $canProcessInvoice = false;
        foreach ($processConditions as $condition) {
            if($condition['order_state'] == $state && $condition['order_status'] == $status && $condition['payment_method'] == $paymentMethod) {
                $canProcessInvoice = true;
                break;
            }
        }

        if ($canProcessInvoice && $order->canInvoice()) {
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();

            $invoice->sendEmail();
        }
    }
}
