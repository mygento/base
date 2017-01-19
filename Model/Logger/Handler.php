<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Model\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{

    public function __construct(
        $name,
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        $filePath = null
    ) {
        $DS = DIRECTORY_SEPARATOR;
        $this->fileName = $DS . 'var' . $DS . 'log' . $DS . $name . '.log';
        parent::__construct($filesystem, $filePath);
    }
}