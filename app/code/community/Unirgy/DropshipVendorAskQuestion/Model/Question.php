<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipVendorAskQuestion
 * @copyright  Copyright (c) 2011-2012 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipVendorAskQuestion_Model_Question extends Mage_Core_Model_Abstract
{
    protected $_eventPrefix = 'udqa_question';
    protected $_eventObject = 'question';

    protected function _construct()
    {
        $this->_init('udqa/question');
    }

    public function afterCommitCallback()
    {
        $this->load($this->getId());
        Mage::helper('udqa')->notifyAdminCustomer($this);
        Mage::helper('udqa')->notifyAdminVendor($this);
        Mage::helper('udqa')->notifyCustomer($this);
        Mage::helper('udqa')->notifyVendor($this);
        return parent::afterCommitCallback();
    }

    protected function _beforeSave()
    {
        if ($this->getAnswerText() && $this->isEmptyAnswerDate()) {
            $this->setAnswerDate(now());
        }
        if ($this->isChangeQuestionStatusToDefault()) {
            $this->setIsAdminQuestionNotified(0);
            $this->setQuestionStatus(Mage::getStoreConfig('udqa/general/default_question_status'));
        }
        if ($this->isChangeAnswerStatusToDefault()) {
            $this->setIsAdminAnswerNotified(0);
            $this->setAnswerStatus(Mage::getStoreConfig('udqa/general/default_answer_status'));
        }
        return parent::_beforeSave();
    }

    public function isChangeQuestionStatusToDefault()
    {
        return !$this->hasQuestionStatus()
            || $this->getQuestionStatus()!=Unirgy_DropshipVendorAskQuestion_Model_Source::UDQA_STATUS_DECLINED
                && !$this->getIsAdminChanges()
                && $this->dataHasChangedFor('question_text')
                && !$this->getIsSkipAutoQuestionStatus();
    }

    public function isChangeAnswerStatusToDefault()
    {
        return !$this->hasAnswerStatus()
            || $this->getAnswerStatus()!=Unirgy_DropshipVendorAskQuestion_Model_Source::UDQA_STATUS_DECLINED
                && !$this->getIsAdminChanges()
                && $this->dataHasChangedFor('answer_text')
                && !$this->getIsSkipAutoAnswerStatus();
    }

    public function isEmptyAnswerDate()
    {
        return Mage::helper('udqa')->isEmptyDate($this->getAnswerDate());
    }

    public function validate()
    {
        $errors = array();
        $qaHlp = Mage::helper('udqa');
        if (!Zend_Validate::is($this->getQuestionText(), 'NotEmpty')) {
            $errors[] = Mage::helper('udropship')->__('Please enter the question text.');
        }
        if (!Zend_Validate::is($this->getCustomerName(), 'NotEmpty')) {
            $errors[] = Mage::helper('udropship')->__('Please enter your name.');
        }
        if (!Zend_Validate::is($this->getCustomerEmail(), 'EmailAddress')) {
            $errors[] = Mage::helper('udropship')->__('Invalid email address "%s".', htmlspecialchars($this->getCustomerEmail()));
        }
        if ($this->getShipmentId()) {
            $shipment = Mage::getModel('sales/order_shipment')->load($this->getShipmentId());
            if (!$shipment->getId()) {
                $errors[] = Mage::helper('udropship')->__('Shipment not found.');
            } else {
                if ($shipment->getCustomerId()!=$this->getCustomerId()) {
                    $errors[] = Mage::helper('udropship')->__('Shipment not found.');
                } else {
                    $this->setVendorId($shipment->getUdropshipVendor());
                }
            }
        }
        return !empty($errors) ? $errors : true;
    }

    public function validateVendor($vId)
    {
        if ($vId instanceof Varien_Object) {
            $vId = $vId->getVendorId();
        }
        return $vId==$this->getVendorId();
    }
    public function validateCustomer($cId)
    {
        if ($cId instanceof Varien_Object) {
            $cId = $cId->getCustomerId();
        }
        return $cId==$this->getCustomerId();
    }


    public function getVendorName()
    {
        $vendors = Mage::getSingleton('udropship/source')->getVendors(true);
        return @$vendors[$this->getVendorId()];
    }

    public function getVendorEmail()
    {
        $vendors = Mage::getSingleton('udropship/source')->getVendorsColumn('email', true);
        return @$vendors[$this->getVendorId()];
    }

    public function canCustomerViewAnswer()
    {
        return $this->getAnswerText()
            && $this->getAnswerStatus()==Unirgy_DropshipVendorAskQuestion_Model_Source::UDQA_STATUS_APPROVED;
    }

    public function canVendorViewQuestion()
    {
        return $this->getQuestionStatus()==Unirgy_DropshipVendorAskQuestion_Model_Source::UDQA_STATUS_APPROVED;
    }

    public function canShowCustomerInfo()
    {
        return Mage::helper('udqa')->canShowCustomerInfo($this);
    }

    public function canShowVendorInfo()
    {
        return Mage::helper('udqa')->canShowCustomerInfo($this);
    }

    public function getVendorUrl()
    {
        return Mage::getModel('core/url')->getUrl('udqa/vendor/questionEdit', array('id'=>$this->getId()));
    }

    public function getAdminUrl()
    {
        return Mage::getModel('adminhtml/url')->getUrl('adminhtml/udqaadmin_index/edit', array('id'=>$this->getId()));
    }

}