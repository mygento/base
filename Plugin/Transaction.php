<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Plugin;

class Transaction
{
    public function afterGetTransactionTypes(
        \Magento\Sales\Model\Order\Payment\Transaction $subject,
        $result
    ) {
        return array_merge($result, [
            'fiscal_receipt'        => __('Fiscal receipt'),
            'fiscal_receipt_refund' => __('Fiscal receipt refund'),
        ]);
    }
}