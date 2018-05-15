<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Helper;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Base Data helper
 */
class Currency extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * Currency constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * @param $amountValue
     * @param null $currencyCodeFrom
     * @param null $currencyCodeTo
     * @return float|int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function convert($amountValue, $currencyCodeFrom = null, $currencyCodeTo = null)
    {
        /**
         * If is not specified the currency code from which we want to convert
         * - use current currency
         */
        if (!$currencyCodeFrom) {
            $currencyCodeFrom = $this->storeManager->getStore()
                ->getCurrentCurrency()->getCode();
        }

        /**
         * If is not specified the currency code to which we want to convert
         * - use base currency
         */
        if (!$currencyCodeTo) {
            $currencyCodeTo = $this->storeManager->getStore()
                ->getBaseCurrency()->getCode();
        }

        /**
         * Do not convert if currency is same
         */
        if ($currencyCodeFrom == $currencyCodeTo) {
            return $amountValue;
        }

        /** @var float $rate */
        // Get rate
        $rate = $this->currencyFactory->create()
            ->load($currencyCodeFrom)->getAnyRate($currencyCodeTo);

        if (!$rate) {
            throw new \Exception(__('Cannot find currency rate'));
        }

        // Get amount in new currency
        $amountValue = $amountValue * $rate;

        return $amountValue;
    }
}
