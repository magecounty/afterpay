<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Model\Adminhtml\Source;

/**
 * Class Paymenttype
 * @package Apexx\Afterpay\Model\Adminhtml\Source
 */
class Paymenttype
{
    /**
     * Different payment type.
     */
    const AFTERPAY_PAYMENT_TYPE = 'Invoice';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
                    [
                        'value' => self::AFTERPAY_PAYMENT_TYPE,
                        'label' => __('Invoice')
                    ]
        ];
    }
}
