<?php

class Rera_CustomEventMail_Model_Observer {

    public function salesOrderSaveCommitAfter($event) {
        $enabled = Mage::getStoreConfig("rera_customeventmail/general/enable");
        if ($enabled) {
            $order = $event->getOrder();
            $orderId = $order->getIncrementId();

            $statuses = array(
                'old' => array(
                    'state'     => strtolower( $order->getOrigData('state') ),
                    'status'    => strtolower( $order->getOrigData('status') )
                ),
                'new' => array(
                    'state'     => strtolower( $order->getState() ),
                    'status'    => strtolower( $order->getStatus() )
                ),
                'config' => explode(",", Mage::getStoreConfig("rera_customeventmail/general/order_status"))
            );

            $payload = array(
              'order' => $order,
              'store_name' => $order->getStoreName(),
              'store_url' =>Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
              'subject' => Mage::getStoreConfig("rera_customeventmail/email/subject"),
              'body' => Mage::getStoreConfig("rera_customeventmail/email/body")
            );

            if ($statuses['old']['status'] !== $statuses['new']['status']) {
                if (in_array($statuses['new']['status'], $statuses['config'])) {
                    if ( !Mage::registry('sales_order_save_commit_after_custom_' . $orderId) ) {
                        $this->sendEmail( $order->getCustomerEmail(), 'order_custom_tpl', $payload );
                        Mage::register('sales_order_save_commit_after_custom_' . $orderId, true);
                        $this->log("Order #$orderId: Custom email sent");
                    }
                    else {
                        $this->log("Order #$orderId: Already sent custom email. Aborting!");
                    }
                }
            }
        }
    }

    public function sendEmail($address, $template, $payload) {
        $from = array(
          'email' => Mage::getStoreConfig('trans_email/ident_general/email'),
          'name' => Mage::getStoreConfig('trans_email/ident_general/name')
        );

        $mailTemplate = Mage::getModel('core/email_template');
        $mailTemplate
          ->setDesignConfig(array('area'=>'frontend', 'store'=>1))
          ->setType('html')
          ->sendTransactional( $template, $from, $address, $address, $payload );

        if (!$mailTemplate->getSentSuccess()) {
            Mage::log("ERROR! Email not sent to $address", null, $config['log']);
        }
    }

    public function log($message) {
        $config = array(
            'enable'    => Mage::getStoreConfig('rera_customeventmail/debug/enable'),
            'log'  => Mage::getStoreConfig("rera_customeventmail/debug/log")
        );

        if ($config['enable'] && $config['log'] && $message) {
            Mage::log($message, null, $config['log']);
        }
    }

}
?>
