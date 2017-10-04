<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Model\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{

    /**
     *
     * @param string $name
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     * @param string $filePath
     */
    public function __construct(
        $name,
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        $filePath = null
    ) {
        $this->fileName = DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
            'log' . DIRECTORY_SEPARATOR . $name . '.log';
        parent::__construct($filesystem, $filePath);
    }
}
