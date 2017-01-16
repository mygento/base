<?php
/**
 * Copyright 2017 Mygento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mygento\Base\Model\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{

    public function __construct(
        $name,
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        $filePath = null
    ) {
        $this->fileName = '/var/log/'.$name.'.log';
        parent::__construct($filesystem, $filePath);
    }
}