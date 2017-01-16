<?php
/**
 * Copyright 2017 Mygento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mygento\Base\Model\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{

    /**
     * @var string
     */
    protected $fileName = '/var/log/mygento.log';

    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::DEBUG;

}
