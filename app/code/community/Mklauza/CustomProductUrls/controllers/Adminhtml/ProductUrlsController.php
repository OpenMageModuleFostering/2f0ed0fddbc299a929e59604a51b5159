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

class Mklauza_CustomProductUrls_Adminhtml_ProductUrlsController extends Mage_Adminhtml_Controller_Action {
    
    public function exampleAction() {
// @test        
//$profiler = Mage::getSingleton('core/resource')->getConnection('core_write')->getProfiler();
//$profiler->setEnabled(true);        
        $pattern = $this->getRequest()->getParam('pattern');
        $patternObject = Mage::getSingleton('mklauza_customproducturls/pattern');
        $data['exampleUrl'] = $patternObject->setPattern($pattern)->getRandomExample();
//Mage::log(print_r($profiler->getQueryProfiles(), true), null, 'queries.log', true);
//Mage::log(array($profiler->getTotalNumQueries(), $profiler->getTotalElapsedSecs()), null, 'queries.log', true);
//$profiler->setEnabled(false);            
        $data['isSuccess'] = true;
        $this->getResponse()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(json_encode($data));
    }
    
}