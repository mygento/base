<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Helper;

class Discount
{
    const VERSION = '1.0.12';

    protected $generalHelper = null;

    protected $_entity           = null;
    protected $_taxValue         = null;
    protected $_taxAttributeCode = null;
    protected $_shippingTaxValue = null;

    protected $_discountlessSum = 0.00;

    /** Есть ли item, цена которого не делится нацело */
    protected $_wryPriceExists = false;

    protected $spreadDiscOnAllUnits = null;

    const NAME_UNIT_PRICE      = 'disc_hlpr_price';
    const NAME_SHIPPING_AMOUNT = 'disc_hlpr_shipping_amount';

    public function __construct(\Mygento\Base\Helper\Data $baseHelper)
    {
        $this->generalHelper = $baseHelper;
    }

    /**
     * @param mixed $entity
     * @param string $taxValue
     * @param string $taxAttributeCode
     * @param string $shippingTaxValue
     * @param bool $spreadDiscOnAllUnits
     * @return array|void
     * @throws \Exception
     */
    public function getRecalculated(
        $entity,
        $taxValue = '',
        $taxAttributeCode = '',
        $shippingTaxValue = ''
    ) {
        if (!$entity) {
            return;
        }

        if (!extension_loaded('bcmath')) {
            $this->generalHelper->addLog('Fatal Error: bcmath php extension is not available.');
            throw new \Exception('BCMath extension is not available in this PHP version.');
        }

        $this->_entity              = $entity;
        $this->_taxValue            = $taxValue;
        $this->_taxAttributeCode    = $taxAttributeCode;
        $this->_shippingTaxValue    = $shippingTaxValue;

        $this->generalHelper->addLog("== START == Recalculation of entity prices. Helper Version: "
            . self::VERSION . ".  Entity class: " . get_class($entity)
            . ". Entity id: {$entity->getId()}");

        $this->runCalculation();

        $this->generalHelper->addLog("== STOP == Recalculation. Entity class: "
            . get_class($entity)
            . ". Entity id: {$entity->getId()}");

        return $this->buildFinalArray();
    }

    protected function runCalculation()
    {
        if ($this->checkSpread()) {
            $this->applyDiscount();
            $this->generalHelper->addLog("'Apply Discount' logic was applied");
            return;
        }
        //Это случай, когда не нужно размазывать копейки по позициям
        //и при этом, позиции могут иметь скидки, равномерно делимые.
        $this->setSimplePrices();
        $this->generalHelper->addLog("'Simple prices' logic was applied");
    }

    public function applyDiscount()
    {
        $subTotal       = $this->_entity->getData('subtotal_incl_tax');
        $shippingAmount = $this->_entity->getData('shipping_incl_tax');
        $grandTotal     = round($this->_entity->getData('grand_total'), 2);
        $grandDiscount  = $grandTotal - $subTotal - $shippingAmount;

        $percentageSum = 0;

        $items    = $this->getAllItems();
        $itemsSum = 0.00;
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $price    = $item->getData('price_incl_tax');
            $qty      = $item->getQty() ?: $item->getQtyOrdered();
            $rowTotal = $item->getData('row_total_incl_tax');

            //Calculate Percentage. The heart of logic.
            $denominator   = ($this->spreadDiscOnAllUnits || ($subTotal == $this->_discountlessSum))
                ? $subTotal
                : ($subTotal - $this->_discountlessSum);
            $rowPercentage = $rowTotal / $denominator;

            if (!$this->spreadDiscOnAllUnits && (floatval($item->getDiscountAmount()) === 0.00)) {
                $rowPercentage = 0;
            }
            $percentageSum += $rowPercentage;

            $discountPerUnit   = $rowPercentage * $grandDiscount / $qty;
            $priceWithDiscount = bcadd($price, $discountPerUnit, 2);

            //Set Recalculated unit price for the item
            $item->setData(self::NAME_UNIT_PRICE, $priceWithDiscount);

            $itemsSum += round($priceWithDiscount * $qty, 2);
        }

        $this->generalHelper->addLog("Sum of all percentages: {$percentageSum}");

        //Calculate DIFF!
        $itemsSumDiff = round($this->slyFloor($grandTotal - $itemsSum - $shippingAmount, 3), 2);

        $this->generalHelper->addLog("Items sum: {$itemsSum}. 
            All Discounts: {$grandDiscount} 
            Diff value: {$itemsSumDiff}");

        if (bccomp($itemsSumDiff, 0.00, 2) < 0) {
            //if: $itemsSumDiff < 0
            $this->generalHelper->addLog(
                "Notice: Sum of all items is greater than sumWithAllDiscount of entity. 
                ItemsSumDiff: {$itemsSumDiff}"
            );

            $itemsSumDiff = 0.0;
        }

        //Set Recalculated Shipping Amount
        $this->_entity->setData(
            self::NAME_SHIPPING_AMOUNT,
            $this->_entity->getData('shipping_incl_tax') + $itemsSumDiff
        );
    }

    /**If everything is evenly divisible - set up prices without extra recalculations
     * like applyDiscount() method does.
     *
     */
    public function setSimplePrices()
    {
        $items = $this->getAllItems();
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $qty      = $item->getQty() ?: $item->getQtyOrdered();
            $rowTotal = $item->getData('row_total_incl_tax');

            $priceWithDiscount = ($rowTotal - $item->getData('discount_amount')) / $qty;
            $item->setData(self::NAME_UNIT_PRICE, $priceWithDiscount);
        }
    }

    public function buildFinalArray()
    {
        $grandTotal = round($this->_entity->getData('grand_total'), 2);

        $items      = $this->getAllItems();
        $itemsFinal = [];
        $itemsSum   = 0.00;
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $taxValue   = $this->_taxAttributeCode
                ? $this->addTaxValue($this->_taxAttributeCode, $item)
                : $this->_taxValue;
            $price      = $item->getData(self::NAME_UNIT_PRICE) !== null
                ? $item->getData(self::NAME_UNIT_PRICE)
                : $item->getData('price_incl_tax');
            $entityItem = $this->_buildItem($item, $price, $taxValue);

            $itemsFinal[$item->getId()] = $entityItem;

            $itemsSum += $entityItem['sum'];
        }

        $receipt = [
            'sum'            => $itemsSum,
            'origGrandTotal' => floatval($grandTotal)
        ];

        $shippingAmount = $this->_entity->getData(self::NAME_SHIPPING_AMOUNT)
            ?: $this->_entity->getData('shipping_incl_tax') + 0.00;
        $shippingName   = $this->_entity->getShippingDescription()
            ?: (
            $this->_entity->getOrder()
                ? $this->_entity->getOrder()->getShippingDescription()
                : ''
            );

        $shippingItem = [
            'name'     => $shippingName,
            'price'    => $shippingAmount,
            'quantity' => 1.0,
            'sum'      => $shippingAmount,
            'tax'      => $this->_shippingTaxValue,
        ];

        $itemsFinal['shipping'] = $shippingItem;
        $receipt['items']       = $itemsFinal;

        if (!$this->_checkReceipt($receipt)) {
            $this->generalHelper->addLog(
                "WARNING: Calculation error! Sum of items is not equal to grandTotal!"
            );
        }

        $this->generalHelper->addLog("Final array:");
        $this->generalHelper->addLog($receipt);

        return $receipt;
    }

    protected function _buildItem($item, $price, $taxValue = '')
    {
        $qty = $item->getQty() ?: $item->getQtyOrdered();
        if (!$qty) {
            throw new \Exception(
                'Divide by zero. Qty of the item is equal to zero! Item: ' . $item->getId()
            );
        }

        $entityItem = [
            'price'    => round($price, 2),
            'name'     => $item->getName(),
            'quantity' => round($qty, 2),
            'sum'      => round($price * $qty, 2),
            'tax'      => $taxValue,
        ];

        $this->generalHelper->addLog("Item calculation details:");
        $this->generalHelper->addLog("
            Item id: {$item->getId()}. 
            Orig price: {$price} 
            Item rowTotalInclTax: {$item->getData('row_total_incl_tax')} 
            PriceInclTax of 1 piece: {$price}. 
            Result of calc:");
        $this->generalHelper->addLog($entityItem);

        return $entityItem;
    }

    /**Validation method. It sums up all items and compares it to grandTotal.
     * @param array $receipt
     * @return bool True if all items price equal to grandTotal. False - if not.
     */
    protected function _checkReceipt(array $receipt)
    {
        $sum = array_reduce($receipt['items'], function ($carry, $item) {
            $carry += $item['sum'];
            return $carry;
        });

        return bcsub($sum, $receipt['origGrandTotal'], 2) === '0.00';
    }

    public function isValidItem($item)
    {
        return $item->getData('row_total_incl_tax') !== null;
    }

    public function slyFloor($val, $precision = 2)
    {
        $factor  = 1.00;
        $divider = pow(10, $precision);

        if ($val < 0) {
            $factor = -1.00;
        }

        return (floor(abs($val) * $divider) / $divider) * $factor;
    }

    protected function addTaxValue($taxAttributeCode, $item)
    {
        if (!$taxAttributeCode) {
            return '';
        }

        return $this->generalHelper->getAttributeValue($taxAttributeCode, $item->getProductId());
    }

    /** It checks do we need to spread discount on all units
     * and sets flag $this->spreadDiscOnAllUnits
     *
     * @return boolean
     */
    public function checkSpread()
    {
        $items = $this->getAllItems();

        $sum                    = 0.00;
        $sumDiscountAmount      = 0.00;
        $this->_discountlessSum = 0.00;
        foreach ($items as $item) {
            $qty      = $item->getQty() ?: $item->getQtyOrdered();
            $rowPrice = $item->getData('row_total_incl_tax') - $item->getData('discount_amount');

            if (floatval($item->getData('discount_amount')) === 0.00) {
                $this->_discountlessSum += $item->getData('row_total_incl_tax');
            }

            /* Означает, что есть item, цена которого не делится нацело*/
            if (!$this->_wryPriceExists) {
                $decimals = $this->getDecimalsCountAfterDiv($rowPrice, $qty);

                $this->_wryPriceExists = $decimals > 2 ? true : false;
            }

            $sum               += $rowPrice;
            $sumDiscountAmount += $item->getData('discount_amount');
        }

        $grandTotal     = round($this->_entity->getData('grand_total'), 2);
        $shippingAmount = $this->_entity->getData('shipping_incl_tax');

        //Есть ли общая скидка на Чек. bccomp returns 0 if operands are equal
        if (bccomp($grandTotal - $shippingAmount - $sum, 0.00, 2) !== 0) {
            $this->generalHelper->addLog("1. Global discount on whole cheque.");

            $this->spreadDiscOnAllUnits = true;
            return true;
        }

        //ok, есть товар, который не делится нацело
        if ($this->_wryPriceExists) {
            $this->generalHelper->addLog("2. Item with price which is not divisible evenly.");

            return true;
        }

        return false;
    }

    public function getDecimalsCountAfterDiv($numerator, $denominator)
    {
        $divRes   = strval(round($numerator / $denominator, 20));
        $decimals = strrchr($divRes, ".") ? strlen(strrchr($divRes, ".")) - 1 : 0;

        return $decimals;
    }

    public function getAllItems()
    {
        return $this->_entity->getAllVisibleItems()
            ? $this->_entity->getAllVisibleItems()
            : $this->_entity->getAllItems();
    }

    public function getVATAmount($price, $VATValue)
    {
        $priceWithoutVat = $price / (1 + $VATValue / 100);
        return round($price - $priceWithoutVat, 2);
    }
}
