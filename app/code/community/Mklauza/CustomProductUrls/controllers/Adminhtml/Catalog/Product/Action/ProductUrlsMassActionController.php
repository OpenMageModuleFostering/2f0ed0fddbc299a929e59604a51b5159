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

class Mklauza_CustomProductUrls_Adminhtml_Catalog_Product_Action_ProductUrlsMassActionController extends Mage_Adminhtml_Controller_Action {
    
    // @overridden
    public function preDispatch() {
        parent::preDispatch();
        if(!Mage::GetStoreConfigFlag('mklauza_customproducturls/general/is_active')) {
            $this->_redirect('adminhtml/dashboard');
            return;
        }
        
    }
  
    protected function _construct()
    {
        // Define module dependent translate
        $this->setUsedModuleName('Mage_Catalog');
    }
    
    public function editAction()
    {
        if (!$this->_validateProducts()) {
            return;
        }

        $this->loadLayout();
        $this->_setActiveMenu('catalog/product');
        
        $formBlock = $this->getLayout()->createBlock('mklauza_customproducturls/adminhtml_form_massaction');
        
        $this->_title('Custom Product Urls')->_addContent($formBlock);
        $this->renderLayout();    
    }

    /**
     * Update product attributes
     */
    public function saveAction()
    {
// @test        
//$profiler = Mage::getSingleton('core/resource')->getConnection('core_write')->getProfiler();
//$profiler->setEnabled(true);
   
        if (!$this->_validateProducts()) {
            $this->_getSession()->addError($this->__('Validation failed.'));
            return $this->_redirectReferer();
        }

        /* Collect Data */
        $pattern  = $this->getRequest()->getParam('pattern', '');
        $reindexCatalogUrls  = (bool) $this->getRequest()->getParam('reindex_catalog_urls', null);
        $urlKeyCreateRedirect = (bool) $this->getRequest()->getParam('url_key_create_redirect', null);

        try {
                $storeId    = $this->_helper()->getSelectedStoreId();

                $attribute = Mage::getSingleton('eav/config')
                    ->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'url_key');
                if (!$attribute->getAttributeId()) {
                    throw new Exception($this->__('url_key attribute obj missing'));
                }

                Mage::getSingleton('mklauza_customproducturls/catalog_resource_product_action')
                        ->updateUrlKeyAttributes(
                                $this->_helper()->getProductIds(), 
                                $pattern, 
                                $urlKeyCreateRedirect, 
                                $storeId
                            );
                
                // reindex product related urls
                if($reindexCatalogUrls) {
                    $urlModel = Mage::getSingleton('catalog/url');     
                    // set save rewrite flag
                    if($urlKeyCreateRedirect) {
                        $urlModel->setShouldSaveRewritesHistory(true);
                    }
                    // reindex product urls
                    $urlModel->clearStoreInvalidRewrites(); // Maybe some products were moved or removed from website
                    foreach ($this->_helper()->getProductIds() as $productId) {
                         $urlModel->refreshProductRewrite($productId);
                    }                  
                }
                 
            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) were updated', count($this->_helper()->getProductIds()))
            );
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('An error occurred while updating the product(s) attributes.'));
        }
//// @test
//Mage::log(print_r($profiler->getQueryProfiles(), true), null, 'queries.log', true);
//Mage::log(array($profiler->getTotalNumQueries(), $profiler->getTotalElapsedSecs()), null, 'queries.log', true);
//$profiler->setEnabled(false);     
//die();
        $this->_redirect('*/catalog_product/', array('store'=>$this->_helper()->getSelectedStoreId()));          
    }

    /**
     * Validate selection of products for massupdate
     *
     * @return boolean
     */
    protected function _validateProducts()
    {
        $error = false;
        $productIds = $this->_helper()->getProductIds();
      
        if (!is_array($productIds)) {
            $error = $this->__('Please select products for attributes update');
        } else if (!Mage::getModel('catalog/product')->isProductsHasSku($productIds)) {
            $error = $this->__('Some of the processed products have no SKU value defined. Please fill it prior to performing operations on these products.');
        }

        if ($error) {
            $this->_getSession()->addError($error);
            $this->_redirect('*/catalog_product/', array('_current'=>true));
        }

        return !$error;
    }

    /**
     * Rertive data manipulation helper
     *
     * @return Mage_Adminhtml_Helper_Catalog_Product_Edit_Action_Attribute
     */
    protected function _helper()
    {
        return Mage::helper('adminhtml/catalog_product_edit_action_attribute');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/update_attributes');
    }

    /**
     * Attributes validation action
     *
     */
    public function validateAction()
    {
        $response = new Varien_Object();
        $response->setError(false);
        $attributesData = $this->getRequest()->getParam('attributes', array());
        $data = new Varien_Object();

        try {
            if ($attributesData) {
                $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
                $storeId    = $this->_helper()->getSelectedStoreId();

                foreach ($attributesData as $attributeCode => $value) {
                    $attribute = Mage::getSingleton('eav/config')
                        ->getAttribute('catalog_product', $attributeCode);
                    if (!$attribute->getAttributeId()) {
                        unset($attributesData[$attributeCode]);
                        continue;
                    }
                    $data->setData($attributeCode, $value);
                    $attribute->getBackend()->validate($data);
                }
            }
        } catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
            $response->setError(true);
            $response->setAttribute($e->getAttributeCode());
            $response->setMessage($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $response->setError(true);
            $response->setMessage($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('An error occurred while updating the product(s) attributes.'));
            $this->_initLayoutMessages('adminhtml/session');
            $response->setError(true);
            $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
        }

        $this->getResponse()->setBody($response->toJson());
    }
    
/* ---------------------------------------------------------------------------- */    
    
    public function clearRedirectsAction() {
//        Mage::getModel('catalog/url')->refreshProductRewrites(0);
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        try {
            $write->beginTransaction();
            $table = Mage::getSingleton('core/resource')->getTableName('core/url_rewrite');
            $count = $write->exec('DELETE FROM ' . $table . ' WHERE options IS NOT NULL AND is_system = 0');
            $write->commit();
            $this->_getSession()->addSuccess($this->__('Successfully removed %s redirects.', $count));
        } catch(Exception $e) {
            $write->rollback();
            $this->_getSession()->addException($this->__("An error occurred while clearing url redirects: %s", $e->getMessage()));            
        }
        
        $this->_redirectReferer();
    }
    
}