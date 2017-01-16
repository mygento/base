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

    /** @var \Mygento\Base\Model\Logger\LoggerFactory */
    protected $_loggerFactory;

    /** @var \Mygento\Base\Model\Logger\Logger */
    protected $_logger;

    /** @var \Mygento\Base\Model\Logger\HandlerFactory */
    protected $_handlerFactory;

    /** @var \Magento\Framework\Encryption\Encrypto */
    protected $_encryptor;

    /** @var string */
    protected $code = 'mygento';

    /** @var \Magento\Directory\Helper\Data */
    protected $_directoryHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mygento\Base\Model\Logger\LoggerFactory $logger
     * @param \Mygento\Base\Model\Logger\HandlerFactory $handler
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory,
        \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor
    ) {
        parent::__construct($context);
        $this->_loggerFactory = $loggerFactory;
        $this->_handlerFactory = $handlerFactory;
        $this->_encryptor = $encryptor;
        
        $this->logger = $this->_loggerFactory->create(['name' => $this->code]);
        $handler = $this->_handlerFactory->create(['name' => $this->code]);
        $this->logger->setHandlers([$handler]);
    }

    public function addLog($text, $isArray = false)
    {
        if ($isArray) {
            // @codingStandardsIgnoreStart
            $text = print_r($text, true);
            // @codingStandardsIgnoreEnd
        }
        
        $this->logger->log('DEBUG', $text);
    }

    /**
     * @param string $path
     */
    public function decrypt($path)
    {
        return $this->_encryptor->decrypt($path);
    }
}
