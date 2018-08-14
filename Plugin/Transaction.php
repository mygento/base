<?php

/**
 * @author Mygento Team
 * @copyright 2014-2018 Mygento (https://www.mygento.ru)
 * @package Mygento_Base
 */

namespace Mygento\Base\Plugin;

class Transaction
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param mixed $result
     */

    public function afterGetTransactionTypes(
        \Magento\Sales\Model\Order\Payment\Transaction $subject,
        $result
    ) {
        return array_merge($result, [
            'fiscal_receipt' => __('Fiscal receipt'),
            'fiscal_refund' => __('Fiscal receipt refund'),
        ]);
    }
}
