<?php

class Mygento_Base_Block_Adminhtml_Info extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

    public function render(Varien_Data_Form_Element_Abstract $element) {

        $helper=Mage::helper('base');
        $site='http://www.mygento.ru';
        $ticket_url="http://team.mygento.net";
        $email='connect@mygento.ru';

        $html='<style>'
                .'#mygento_info{width:600px;}'
                .'#mygento_info tr{padding-bottom:15px;display:block;}'
                .'#mygento_info td:nth-child(1){width:150px;font-weight:bold;}'
                .'#mygento_info td:nth-child(2){width:450px;}'
                .'.line{border-top: 1px solid #c6c6c6;padding-top:15px;}'
                .'}</style>';
        $html.='<table id="mygento_info" cellspacing="0" cellpading="0">';
        $html.='<tr><td>'.$helper->__('Support:').'</td><td>'.$helper->__('Paid extensions support is available through <a href="%s" target="_blank">ticket tracking system</a>',$ticket_url).'.<br/><br/>'.$helper->__('Please report is all bugs and feature requests.').'<br/><br/>'.$helper->__('If for some reasons you can not submit ticket to our system, you can write us an email %s.',$email).'</td></tr>';
        $html.='<tr><td>'.$helper->__('License:').'</td><td>'.$helper->__('You can check tender offer <a href="http://www.mygento.ru/oferta" target="_blank">here</a>').'</td></tr>';
        $html.='<tr class="line"><td><img src="//www.mygento.ru/media/wysiwyg/logo_base.png" width="100" height="133"/></td><td>'.$helper->__('You can hire us for any Magento extension modification.<br/>Write us to %s',$email).'<br/><br/>'.$helper->__('You can check all providable services on <a href=""%s"" target=""_blank"">our website</a>.',$site).'</td></tr>';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.='';
        $html.= '</table>';

        return $html;
    }

}