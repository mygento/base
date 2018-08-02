<?php

/**
 * @author Mygento Team
 * @copyright 2016-2018 Mygento (https://www.mygento.ru)
 * @package Mygento_Base
 */

namespace Mygento\Base\Model\Logger;

class Logger extends \Magento\Framework\Logger\Monolog
{
    /**
     *
     * @param string $name
     * @param \Mygento\Base\Model\Logger\Handler $handler
     */
    public function __construct(
        $name
    ) {
        parent::__construct(
            $name,
            []
        );
    }
}
