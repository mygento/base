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

    /**@var \Magento\Catalog\Model\Product */
    protected $_tempProduct = null;

    /**@var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $_productRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory,
        \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        parent::__construct($context);
        $this->_loggerFactory     = $loggerFactory;
        $this->_handlerFactory    = $handlerFactory;
        $this->_encryptor         = $encryptor;
        $this->_curlClient        = $curl;
        $this->_productRepository = $productRepository;

        $this->_logger = $this->_loggerFactory->create(['name' => $this->_code]);
        $handler       = $this->_handlerFactory->create(['name' => $this->_code]);
        $this->_logger->setHandlers([$handler]);
    }

    /**
     *
     * @param string|array $text
     */
    public function addLog($text)
    {
        if (!$this->getConfig($this->getDebugConfigPath())) {
            return;
        }

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
     * @param string $config_path
     * @return string
     */
    public function getConfig($configPath)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string like 'smsmodule/general/debug'
     */
    protected function getDebugConfigPath()
    {
        return $this->_code . '/general/debug';
    }

    public function getProduct($productId)
    {
        return $this->_productRepository->getById($productId);
    }

    /**
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return mixed
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
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return mixed
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
     * @param string $phone
     * @return string
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

    public function getAttributeValue($attributeCode, $productId)
    {
        $product = $this->getProduct($productId);
        $value   = $product->getAttributeText($attributeCode) !== null
            ? $product->getAttributeText($attributeCode)
            : $product->getData($attributeCode);

        return $value;
    }

    /**
     * Retrieve url
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route, $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    /**
     * Force convert to float
     *
     * @param mixed $value
     * @return  float
     */
    public function formatToNumber($value)
    {
        $value = floatval(str_replace(
            [' ', ','],
            ['', '.'],
            $value
        ));
        return $value;
    }
}
