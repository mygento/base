<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Base
 * @copyright Copyright © 2014 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Base_Block_Adminhtml_Modules extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

    public function render(Varien_Data_Form_Element_Abstract $element) {

        $html='';

        $modules=array_keys((array) Mage::getConfig()->getNode('modules')->children());

        sort($modules);

        foreach ($modules as $moduleName) {
            if ($moduleName == 'Mygento_Base') {
                continue;
            }
            if (strpos($moduleName,'Mygento_') !== false) {
                $html.=$moduleName.' ('.Mage::getConfig()->getModuleConfig($moduleName)->version.')<br/>';
            }
        }


        return $html;
    }

}
