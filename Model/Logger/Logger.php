<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Model\Logger;

class Logger extends \Magento\Framework\Logger\Monolog
{

    public function __construct(
        $name,
        \Mygento\Base\Model\Logger\Handler $handler
        )
    {
        parent::__construct(
            $name, [$handler] // only one handler
        );
    }
}
