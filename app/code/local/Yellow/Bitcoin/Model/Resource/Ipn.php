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
 **/

class Yellow_Bitcoin_Model_Resource_Ipn extends Mage_Core_Model_Resource_Db_Abstract {

    protected function _construct() {
        $this->_init('bitcoin/ipn', 'id');
    }
    
    public function MarkAsUnconfirmedPayment($invoice_id){
        return $this->updatePayment($invoice_id , "unconfirmed");
    }
    
    
    public function MarkAsPartial($invoice_id){
        return $this->updatePayment($invoice_id , "partial");
    }
    
    private function updatePayment($invoice_id , $status){
        $wa = $this->_getWriteAdapter();
        try {
            $fields = $where = array();
            $wa->beginTransaction();
            $fields[] = $wa->quoteInto('status=?', $status);
            $where[]  = $wa->quoteInto('status=?', "new");
            $where[]  = $wa->quoteInto('invoice_id =?', $invoice_id);
            $tableName = $this->getTable("bitcoin/ipn");
            $wa->update($tableName, $fields ,$where);
            $wa->commit();
            return true;
        } catch (Exception $exc) {
            $wa->rollBack();
            return $exc->getMessage();
        }
    }
}
