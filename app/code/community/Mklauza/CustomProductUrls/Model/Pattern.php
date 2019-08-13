<?php
/**
 * Marcin Klauza - Magento developer
 * http://www.marcinklauza.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to marcinklauza@gmail.com so we can send you a copy immediately.
 *
 * @category    Mklauza
 * @package     Mklauza_CustomProductUrls
 * @author      Marcin Klauza <marcinklauza@gmail.com>
 * @copyright   Copyright (c) 2015 (Marcin Klauza)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mklauza_CustomProductUrls_Model_Pattern extends Varien_Object {
    
    private $_attributesCollection;
    private $_productAttributes;
    private $_attributesChunks;
    private $_patternChunks; 
    
    private function getAttributesCollection() {
       
//        $attributesCache = Mage::app()->getCache()->load('mklauza_cusomproducturls_product_attribute_collection');
//        if(!$this->_attributesCollection && !empty($attributesCache)) {
//            $this->_attributesCollection = Mage::getResourceModel('catalog/product_attribute_collection');
//            $this->_attributesCollection->setData(unserialize($attributesCache));
//        }
        if(!$this->_attributesCollection) {
            // Mage_Catalog_Model_Resource_Product_Attribute_Collection
            $this->_attributesCollection =  Mage::getResourceModel('catalog/product_attribute_collection')
                    ->addVisibleFilter('is_visible_on_front', array('=' => '1'))
                    ->addFieldTofilter('attribute_code', array('neq' => 'url_key'));
            
//                $this->_attributesCollection->initCache(
//                        Mage::app()->getCache()->setLifetime(3*60), 
//                        'Bestsellers_lalala', //this is just custom prefix
//                        array('collections')
//                    );            

//            Mage::app()->getCache()->remove('mklauza_cusomproducturls_product_attribute_collection');
//            Mage::app()->getCache()->save(
//                    serialize($this->_attributesCollection->getData()), 
//                    "mklauza_cusomproducturls_product_attribute_collection", 
//                    array("mklauza_cusomproducturls"), 
//                    3*60
//                );
//            var_dump($this->_attributesCollection);
        }
        
        return $this->_attributesCollection;
    }   
    
    public function getAllProductAttributes() {
        if(!$this->_productAttributes) {
            $attributes = array();
            foreach ($this->getAttributesCollection() as $productAttr) { /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttr */
                $attributes[$productAttr->getAttributeCode()] = $productAttr->getFrontendLabel();//Mage::helper('core')->jsQuoteEscape($productAttr->getFrontendLabel());
            }
            $this->_productAttributes = $attributes;
        }
        return $this->_productAttributes;
    }        
    
    public function getAllProductAttributesChunks() {
        if($this->_attributesChunks == null) {
            $attributes = $this->getAllProductAttributes();
            $patternChunks = $this->getPatternChunks();
         
            foreach($patternChunks as $chunk) {
                if($chunk['type'] === 'attribute') {
                    $attrId = $chunk['value'];
                    unset($attributes[$attrId]);
                }
            }

            $this->_attributesChunks = array();          
            foreach($attributes as $code => $label) {
                $this->_attributesChunks[] = array('value' => $code, 'type' => 'attribute');    
            }
        }

        return $this->_attributesChunks;
    }    
    
    // ----------------------------------------------------------------------
    
//    public function getPattern() {
//        if(!$this->getData('pattern')) {
//            throw new Exception('Url Pattern not specified. Set pattern first.');
//        }
//        return $this->getData('pattern');
//    }
    
    private function _getPatternChunks() {
        $urlPattern = $this->getPattern();
        
        $regex = '/(\{(\w+)\})?([^\{\}]+)?/';

        $chunks = array();
        $doesMatch = true;
        while(strlen($urlPattern) && $doesMatch)
        {
            $matches = array();
            $doesMatch = preg_match($regex, $urlPattern, $matches);      
            if(!empty($matches[2])) {
                $chunks[] = array('value' => $matches[2], 'type' => 'attribute');
            }
            if(!empty($matches[3])) {
                $chunks[] = array('value' => $matches[3], 'type' => 'text');
            }
            
            $urlPattern = str_replace($matches[0], '', $urlPattern);
        }   

        return $chunks;
    }    
    
    public function getPatternChunks() {
        if($this->_patternChunks === null) {
            $this->_patternChunks = $this->_getPatternChunks();
        }
        return $this->_patternChunks;        
    }
    
    private function getPatternAttributesValues() {
        $chunks = $this->getPatternChunks();
        foreach($chunks as $chunk) {
            if($chunk['type'] === 'attribute') {
                $attributes[] = $chunk['value'];
            }
        }
        $allAttributes = array();
        foreach($this->getAttributesCollection() as $_attribute) {
            $allAttributes[$_attribute->getAttributeCode()] = $_attribute;
        }
        $attributes = array_flip($attributes);        
        foreach($attributes as $_code => $val) {
            $attribute = $allAttributes[$_code];//Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $_code);
            if ($attribute->usesSource()) {
                $htmlOptions = $attribute->getSource()->getAllOptions(false);
                if($htmlOptions && is_array($htmlOptions) && count($htmlOptions)) 
                {
                    $options = array();
                    foreach($htmlOptions as $option) {
                        $options[$option['value']] = $option['label'];
                    }
                    $attributes[$_code]= $options;                    
                }
            }            
        }
        return $attributes;
    }
    
    public function prepareUrlKey($productId, $storeId) {       
//// @test        
$profiler = Mage::getSingleton('core/resource')->getConnection('core_write')->getProfiler();
$profiler->setEnabled(true);            
        $chunks = $this->getPatternChunks();
        $patternAttributes = $this->getPatternAttributesValues();
//// @test
Mage::log(print_r($profiler->getQueryProfiles(), true), null, 'queries.log', true);
Mage::log(array($profiler->getTotalNumQueries(), $profiler->getTotalElapsedSecs()), null, 'summary.log', true);
$profiler->setEnabled(false);

        foreach($patternAttributes as $code => &$value) {
            $rawValue = Mage::getModel('catalog/product')->getResource()->getAttributeRawValue($productId, $code, $storeId);
            // options
            if(is_array($value)) {
                $textOption = $value[$rawValue];
                $value = $textOption;
            } elseif($code === 'price') {
                $value =  number_format($rawValue, 2);
            } else {
                $value = $rawValue;
            }
        }

        $url_str = '';
        foreach($chunks as $chunk) {
            if($chunk['type'] === 'attribute') {
                $attrCode = $chunk['value'];                 
                $url_str .= $patternAttributes[$attrCode];
            } elseif($chunk['type'] === 'text') {
                $url_str .= $chunk['value'];
            }
        }

        $url_key = Mage::getModel('catalog/product_url')->formatUrlKey($url_str);
        return $url_key;
    }   
    
    public function getRandomExample() {
        $storeId = Mage::app()->getStore()->getStoreId();
    
        // get random product Id
        $collection = Mage::getResourceModel('catalog/product_collection')
                ->addStoreFilter($storeId)
                ->addFieldToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE));
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('entity_id')->order(new Zend_Db_Expr('RAND()'))->limit(1);
        $productId = $collection->getFirstItem()->getId();
           
        $url_key = $this->prepareUrlKey($productId, $storeId);
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $url_key . Mage::getStoreConfig('catalog/seo/product_url_suffix');
    }    
}