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

abstract class Mklauza_CustomProductUrls_Block_Adminhtml_Form_Abstract extends Mage_Adminhtml_Block_Template {

    private $_allowedFormats = array('price');//, 'date', 'price', 'wee');
    
    public function _construct() {
        if( $this->getPatternObject()->getPattern() === null) {
            if(Mage::app()->getRequest()->getParam('pattern', null)) {
                $this->getPatternObject()->setPattern(Mage::app()->getRequest()->getParam('pattern'));
            } else {
                $this->getPatternObject()->setPattern($this->_helper()->getConfigPattern());
            }
        }
    }
    
    public abstract function getSubmitUrl();
    
    public function getExampleUrl() {
        return Mage::helper('adminhtml')->getUrl('adminhtml/ProductUrls/example');
    }
    
    protected function getPatternObject() {
        return Mage::getSingleton('mklauza_customproducturls/pattern');
    }
    
    private function render(array $chunk = null) {
        if(!$chunk || !isset($chunk['value']) || !isset($chunk['type'])) {
            return '';
        }
        
        if($chunk['type'] === 'attribute') {
            $attributes = $this->getPatternObject()->getAllProductAttributes();
            $attrId = $chunk['value'];
            return '<span class="inputTags-item blocked" data-value="' . $attrId . '">'
                    . '<span class="value">' . $attributes[$attrId] . '</span>'
                .'</span>';            
        } elseif($chunk['type'] === 'text') {
            return '<input type="text" class="inputTags-field" value="' . $chunk['value'] . '"/>';
        }
    }
    
    public function getPatternHtml() {
        $chunks = $this->getPatternObject()->getPatternChunks();
        $html = '';
        foreach ($chunks as $_chunk) {
            $html .= $this->render($_chunk);
        }
        
        return $html;
    }    
    
    public function getAttributesCloudHtml() {
        $chunks = $this->getPatternObject()->getAllProductAttributesChunks();
        $html = '';
        foreach ($chunks as $_chunk) {
            $html .= $this->render($_chunk);
        }
        return $html;
    }
    
    protected function _helper() {
        return Mage::helper('mklauza_customproducturls');
    }
    
}