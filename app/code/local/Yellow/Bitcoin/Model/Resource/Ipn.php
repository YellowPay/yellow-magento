<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 YellowPay.co
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * */
class Yellow_Bitcoin_Model_Resource_Ipn extends Mage_Core_Model_Resource_Db_Abstract {

    protected function _construct() {
        $this->_init('bitcoin/ipn', 'id');
    }

    public function MarkAsAuthorizing($invoice_id) {
        return $this->updatePayment($invoice_id, "authorizing");
    }

    public function MarkAsPaid($invoice_id) {
        return $this->updatePayment($invoice_id, "paid");
    }

    public function MarkAsPartial($invoice_id) {
        return $this->updatePayment($invoice_id, "partial");
    }

    public function MarkAsExpired($invoice_id) {
        return $this->updatePayment($invoice_id, "expired");
    }

    public function MarkAsRefundOwed($invoice_id) {
        return $this->updatePayment($invoice_id, "refund_owed");
    }

    public function MarkAsRefundPaid($invoice_id) {
        return $this->updatePayment($invoice_id, "refund_paid");
    }

    public function MarkAsRefundRequested($invoice_id) {
        return $this->updatePayment($invoice_id, "refund_requested");
    }



    private function updatePayment($invoice_id, $status) {
        $wa = $this->_getWriteAdapter();
        try {
            $timezone = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
            $now = new \DateTime("now",new \DateTimeZone($timezone));
            $where = array();
            $wa->beginTransaction();
            $fields["status"]  = $status;
            $fields["updated_at"] = $now->format("Y-m-d H:i:s");
            $where[]   = $wa->quoteInto("invoice_id = ?", $invoice_id);
            $tableName = $this->getTable("bitcoin/ipn");
            $wa->update($tableName, $fields, $where);
            $wa->commit();
            return true;
        } catch (Exception $exc) {
            $wa->rollBack();
            return $exc->getMessage();
        }
    }

}
