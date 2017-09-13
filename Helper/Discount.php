<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */
namespace Mygento\Base\Helper;

//TODO: FIgure out - do we need to extend this parent helper?

/**
 * Module helper
 */
class Discount extends \Mygento\Base\Helper\Data
{
    /**@var \Magento\Catalog\Model\ProductRepository */
    protected $_productRepository;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory
     * @param \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory,
        \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        parent::__construct(
            $context,
            $loggerFactory,
            $handlerFactory,
            $encryptor,
            $curl,
            $productRepository
        );

        $this->_productRepository = $productRepository;
    }

    /**
     *
     */
    const VERSION = '1.0.3';

    // @codingStandardsIgnoreStart
    /**
     * Returns item's data as array with properly calculated discount
     *
     * @param $entity \Magento\Sales\Model\Order | \Magento\Sales\Model\Order\Invoice | \Magento\Sales\Model\Order\Creditmemo
     * @param $itemSku
     * @param string $taxValue
     * @param string $taxAttributeCode
     * @param string $shippingTaxValue
     * @return array|mixed
     */
    public function getItemWithDiscount(
        $entity,
        $itemSku,
        $taxValue = '',
        $taxAttributeCode = '',
        $shippingTaxValue = ''
    ) {

        $items = $this->getRecalculated($entity, $taxValue, $taxAttributeCode, $shippingTaxValue)['items'];

        return isset($items[$itemSku]) ? $items[$itemSku] : [];
    }

    /**
     * Returns all items of the entity (order|invoice|creditmemo) with properly calculated discount and properly calculated Sum
     *
     * @param $entity \Magento\Sales\Model\Order | \Magento\Sales\Model\Order\Invoice | \Magento\Sales\Model\Order\Creditmemo
     * @param string $taxValue
     * @param string $taxAttributeCode Set it if info about tax is stored in product in certain attr
     * @param string $shippingTaxValue
     * @return array with calculated items and sum
     */
    public function getRecalculated(
        $entity,
        $taxValue = '',
        $taxAttributeCode = '',
        $shippingTaxValue = ''
    ) {

        $this->addLog("== START == Recalculation of entity prices. Entity class: " . get_class($entity) . ". Entity id: {$entity->getId()}");

        $subTotal       = $entity->getData('subtotal');
        $shippingAmount = $entity->getData('shipping_amount');
        $grandTotal     = $entity->getData('grand_total');
        $grandDiscount  = $grandTotal - $subTotal - $shippingAmount;

        $percentageSum = 0;

        $items      = $entity->getAllVisibleItems() ? $entity->getAllVisibleItems() : $entity->getAllItems();
        $itemsFinal = [];
        $itemsSum   = 0.00;
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $taxValue = $taxAttributeCode ? $this->addTaxValue($taxAttributeCode, $entity, $item) : $taxValue;

            $price    = $item->getData('price');
            $qty      = $item->getQty() ?: $item->getQtyOrdered();
            $rowTotal = $item->getData('row_total');

            //Calculate Percentage. The heart of logic.
            $rowPercentage = $rowTotal / $subTotal;
            $percentageSum += $rowPercentage;

            $discountPerUnit   = $rowPercentage * $grandDiscount / $qty;
            $priceWithDiscount = $this->slyFloor($price + $discountPerUnit);

            $entityItem = $this->_buildItem($item, $priceWithDiscount, $taxValue);

            $itemsFinal[$item->getId()] = $entityItem;
            $itemsSum                   += $entityItem['sum'];
        }

        $this->addLog("Sum of all percentages: {$percentageSum}");

        //Calculate DIFF!
        $itemsSumDiff = round($this->slyFloor($grandTotal - $itemsSum - $shippingAmount, 3), 2);

        $this->addLog("Items sum: {$itemsSum}. All Discounts: {$grandDiscount} Diff value: {$itemsSumDiff}");
        if (bccomp($itemsSumDiff, 0.00, 2) < 0) {
            //if: $itemsSumDiff < 0
            $this->addLog("Notice: Sum of all items is greater than sumWithAllDiscount of entity. ItemsSumDiff: {$itemsSumDiff}");
            $itemsSumDiff = 0.0;
        }

        $receipt = [
            'sum'            => $itemsSum,
            'origGrandTotal' => floatval($grandTotal)
        ];

        $shippingItem = [
            'name'     => $this->getShippingName($entity),
            'price'    => $entity->getShippingAmount() + $itemsSumDiff,
            'quantity' => 1.0,
            'sum'      => $entity->getShippingAmount() + $itemsSumDiff,
            'tax'      => $shippingTaxValue,
        ];

        $itemsFinal['shipping'] = $shippingItem;
        $receipt['items']       = $itemsFinal;

        if (!$this->_checkReceipt($receipt)) {
            $this->addLog("WARNING: Calculation error! Sum of items is not equal to grandTotal!");
        }
        $this->addLog("Final result of recalculation:");
        $this->addLog($receipt);
        $this->addLog("== STOP == Recalculation of entity prices. ");

        return $receipt;
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice\Item | \Magento\Sales\Model\Order\Creditmemo\Item $item
     * @param int $price
     * @param string $taxValue
     * @return array
     * @throws \Exception
     */
    protected function _buildItem($item, $price, $taxValue = '')
    {

        $qty = $item->getQty() ?: $item->getQtyOrdered();
        if (!$qty) {
            throw new \Exception('Divide by zero. Qty of the item is equal to zero! Item: ' . $item->getId());
        }

        $entityItem = [
            'price'    => round($price, 2),
            'name'     => $item->getName(),
            'quantity' => round($qty, 2),
            'sum'      => round($price * $qty, 2),
            'tax'      => $taxValue,
        ];

        $this->addLog("Item calculation details:");
        $this->addLog("Item id: {$item->getId()}. Orig price: {$price} Item rowTotal: {$item->getRowTotal()} Price of 1 piece: {$price}. Result of calc:");
        $this->addLog($entityItem);

        return $entityItem;
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice $entity
     * @return string
     */
    public function getShippingName($entity)
    {
        return $entity->getShippingDescription() ?: ($entity->getOrder() ? $entity->getOrder()->getShippingDescription() : '');
    }

    /**
     * Validation method. It sums up all items and compares it to grandTotal.
     *
     * @param array $receipt
     * @return bool True if all items price equal to grandTotal. False - if not.
     */
    protected function _checkReceipt(array $receipt)
    {
        $sum = array_reduce(
            $receipt['items'],
            function ($carry, $item) {
                $carry += $item['sum'];
                return $carry;
            }
        );

        return bcsub($sum, $receipt['origGrandTotal'], 2) === '0.00';
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice\Item | \Magento\Sales\Model\Order\Creditmemo\Item $item
     * @return boolean
     */
    public function isValidItem($item)
    {
        return $item->getRowTotal() && $item->getRowTotal() !== '0.0000';
    }

    /**
     * @param int $val
     * @param int $precision
     * @return int
     */
    public function slyFloor($val, $precision = 2)
    {
        $factor  = 1.00;
        $divider = pow(10, $precision);

        if ($val < 0) {
            $factor = -1.00;
        }

        return (floor(abs($val) * $divider) / $divider) * $factor;
    }

    /**
     * @param type $taxAttributeCode
     * @param type $entity
     * @param type $item
     * @return string
     */
    protected function addTaxValue($taxAttributeCode, $entity, $item)
    {
        if (!$taxAttributeCode) {
            return '';
        }
        $storeId = $entity->getStoreId();

        $store = $storeId ? $this->_storeManager->getStore($storeId) : $this->_storeManager->getStore();

        //TODO: Use parent helper here
        $taxValue = $this->_productResource->getAttributeRawValue(
            $item->getProductId(),
            $taxAttributeCode,
            $store
        );

        $attributeModel = $this->_eavConfig->getAttribute('catalog_product', $taxAttributeCode);

        if ($attributeModel->getData('frontend_input') == 'select') {
            $taxValue = $attributeModel->getSource()->getOptionText($taxValue);
        }
        return $taxValue;
    }

    // @codingStandardsIgnoreEnd
}
