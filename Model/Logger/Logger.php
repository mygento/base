<?php
/**
 * Copyright 2017 Mygento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mygento\Base\Model\Logger;

class Logger extends \Magento\Framework\Logger\Monolog
{

    public function __construct(\Mygento\Base\Model\Logger\Handler $handler)
    {
        parent::__construct(
            'mygento', [$handler] // only one handler
        );
    }
}
