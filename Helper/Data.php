<?php
/**
 * Copyright 2017 Mygento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mygento\Base\Helper;

/**
 * Base Data helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /** @var \Psr\Log\LoggerInterface */
    protected $_logger;

    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected $_encryptor;

    /**
     *
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\Logger $logger,
        \Magento\Framework\Encryption\Encryptor $encryptor
    ) {
        parent::__construct($context);
        $this->_logger = $logger;
        $this->_encryptor = $encryptor;
    }

    public function addLog($text, $isArray = false)
    {
        if (!$this->getConfig('debug')) {
            return;
        }
        if ($isArray) {
            // @codingStandardsIgnoreStart
            $this->_logger->log('DEBUG', print_r($text, true));
            // @codingStandardsIgnoreEnd
            return;
        }
        $this->_logger->log('DEBUG', $text);
    }

    /**
     * @param string $path
     */
    public function decrypt($path)
    {
        return $this->_encryptor->decrypt($path);
    }
}
