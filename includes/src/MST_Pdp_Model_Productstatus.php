<?php
/**
* Magento Support Team.
* @category   MST
* @package    MST_Pdp
* @version    2.0
* @author     Magebay Developer Team <info@magebay.com>
* @copyright  Copyright (c) 2009-2013 MAGEBAY.COM. (http://www.magebay.com)
*/
class MST_Pdp_Model_Productstatus extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('pdp/productstatus');
    }
	public function setProductConfig($data) {
		$id = NULL;
		$collection = $this->getCollection();
		$collection->addFieldToFilter('product_id', $data['product_id']);
		if ($collection->count() > 0) {
			$id = $collection->getFirstItem()->getId();
		}
		$this->setData($data)->setId($id)->save();
	}
	public function getProductStatus($productId) {
		$productConfigs = $this->getProductConfig($productId);
        if(isset($productConfigs['status'])) {
            return $productConfigs['status'];    
        }
        return 2;
	}
	public function getConfigNote($productId) {
		$collection = $this->getCollection();
		$collection->addFieldToFilter('product_id', $productId);
		if ($collection->count() > 0) {
			$data = $collection->getFirstItem()->getData();
			$note = array();
			if ($data['note']) {
				$note = json_decode($data['note'], true);
                if(!isset($note['default_color']) || (isset($note['default_color']) && !$note['default_color'])) {
                    $note['default_color'] = Mage::getStoreConfig("pdp/design/default_object_color");
                }
                if(!isset($note['default_fontsize']) || (isset($note['default_fontsize']) && !$note['default_fontsize'])) {
                    $note['default_fontsize'] = Mage::getStoreConfig("pdp/design/default_object_fontsize");
                }
                if(!isset($note['default_fontheight']) || (isset($note['default_fontheight']) && !$note['default_fontheight'])) {
                    $note['default_fontheight'] = Mage::getStoreConfig("pdp/design/default_object_fontheight");
                }
			}
			//Check product status more details 
			//--Check product has side to design or not--
			$sideModel = Mage::getModel('pdp/pdpside')->getDesignSides($productId);
			$isPdpEnable = Mage::getStoreConfig('pdp/setting/enable');
			if (!$sideModel->count() || $isPdpEnable == 0) {
				$data['status'] = 0;
			}
			$finalArr = array_merge($data, $note);
			//End check status
			return $finalArr;
		}
		return null;
	}
	public function getProductConfig($productId) {
		$note = null;
		if ($this->getConfigNote($productId)) {
			$note = $this->getConfigNote($productId);
		}
        //Default config
        $defaultConfig = $this->getProductDefaultConfig();
        if($note['selected_image'] == "") {
            $note['selected_image'] = $defaultConfig['selected_image'];
        }
		return $note;
	}
    /**
    If product have no config, then show all categories of image by default(show all clipart, frame, background, image, shape, ...)
    Skip step of create PDC product
    **/
    public function getProductDefaultConfig() {
        //Selected Image Categories
        $categories = array();
        $imageCategories = Mage::getModel("pdp/artworkcate")->getArtworkCateCollection();
        if($imageCategories->count()) {
            foreach($imageCategories as $_category) {
                $categories[$_category->getId()] = array('position' => $_category->getPosition());
            }   
        }
        return array(
            'selected_image' => json_encode($categories),
        );
    }
    //Type: selected_image, slected_color, selcted_font
    public function saveSelectedItem($productId, $type, $selectedItem) {
        $collection = $this->getCollection();
        $collection->addFieldToFilter("product_id", $productId);
        if($collection->count()) {
            $productStatus = $collection->getFirstItem()->setData($type, $selectedItem)->save();
            return $productStatus->getData($type);
        } else {
            $_tempData[$type] = $selectedItem;
            $_tempData['product_id'] = $productId;
            $this->setData($_tempData)->save();
            return $this->getData($type);
        }
        return false;
    }
}