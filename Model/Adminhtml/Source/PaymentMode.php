<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Model\Adminhtml\Source;

/**
 * Class PaymentMode
 * @package Apexx\Afterpay\Model\Adminhtml\Source
 */
class PaymentMode
{
    public function toOptionArray()
    {
        return [
                    ['value' => 'TEST', 'label' => __('Test')],
                    ['value' => 'LIVE', 'label' => __('Live')],
        ];
    }
}
