<?php

/**
 * @author Mygento Team
 * @copyright 2014-2018 Mygento (https://www.mygento.ru)
 * @package Mygento_Base
 */

namespace Mygento\Base\Model\Source;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    const UNDEFINED_OPTION_LABEL = '-- Please Select --';

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;
    /**
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     */
    public function __construct(\Magento\Sales\Model\Order\Config $orderConfig)
    {
        $this->_orderConfig = $orderConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = $this->_orderConfig->getStatuses();
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        foreach ($statuses as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }
        return $options;
    }
}
