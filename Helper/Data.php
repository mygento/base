<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Helper;

/**
 * Base Data helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /* @var \Mygento\Base\Model\Logger\LoggerFactory */
    protected $_loggerFactory;

    /* @var \Mygento\Base\Model\Logger\Logger */
    protected $_logger;

    /* @var \Mygento\Base\Model\Logger\HandlerFactory */
    protected $_handlerFactory;

    /* @var \Magento\Framework\Encryption\Encrypto */
    protected $_encryptor;

    /* @var string */
    protected $_code = 'mygento';

    /* @var \Magento\Directory\Helper\Data */
    protected $_directoryHelper;

    /* @var \Magento\Framework\HTTP\Client\Curl */
    protected $_curlClient;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory,
        \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        parent::__construct($context);
        $this->_loggerFactory = $loggerFactory;
        $this->_handlerFactory = $handlerFactory;
        $this->_encryptor = $encryptor;
        $this->_curlClient = $curl;

        $this->_logger = $this->_loggerFactory->create(['name' => $this->_code]);
        $handler = $this->_handlerFactory->create(['name' => $this->_code]);
        $this->_logger->setHandlers([$handler]);
    }

    /**
     *
     * @param string|array $text
     */
    public function addLog($text)
    {
        if (is_array($text)) {
            // @codingStandardsIgnoreStart
            $text = print_r($text, true);
            // @codingStandardsIgnoreEnd
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

    /**
     *
     * @param type $config_path
     * @return type
     */
    public function getConfig($configPath)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     *
     * @param type $url
     * @param type $data
     * @param type $headers
     * @return type
     */
    public function requestApiGet($url, $data, $headers = [])
    {
        // @codingStandardsIgnoreStart
        $curlh = curl_init();
        curl_setopt($curlh, CURLOPT_URL, $url . "?" . http_build_query($data));
        curl_setopt($curlh, CURLOPT_POST, false);
        curl_setopt($curlh, CURLOPT_HEADER, 0);
        curl_setopt($curlh, CURLOPT_RETURNTRANSFER, true);

        foreach ($headers as $header) {
            curl_setopt($curlh, CURLOPT_HTTPHEADER, [$header]);
        }

        $result = curl_exec($curlh);
        curl_close($curlh);
        // @codingStandardsIgnoreEnd
        $this->addLog($result, true);
        return $result;
    }

    /**
     *
     * @param type $url
     * @param type $data
     * @param type $headers
     * @return type
     */
    public function requestApiPost($url, $data, $headers = [])
    {
        // @codingStandardsIgnoreStart
        $curlh = curl_init();
        curl_setopt($curlh, CURLOPT_URL, $url);
        curl_setopt($curlh, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curlh, CURLOPT_POST, true);
        curl_setopt($curlh, CURLOPT_HEADER, 0);
        curl_setopt($curlh, CURLOPT_RETURNTRANSFER, true);

        foreach ($headers as $header) {
            curl_setopt($curlh, CURLOPT_HTTPHEADER, [$header]);
        }

        $result = curl_exec($curlh);
        curl_close($curlh);
        // @codingStandardsIgnoreEnd
        $this->addLog($result, true);
        return $result;
    }

    /**
     *
     * @param type $phone
     * @return type
     */
    public function normalizePhone($phone)
    {
        return preg_replace('/\s+/', '', str_replace(['(', ')', '-', ' '], '', trim($phone)));
    }

    /**
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function getCurlClient()
    {
        return $this->_curlClient;
    }
    
    /**
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }
}
