<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Model\Adminhtml\Source;

/**
 * Class CustomerType
 * @package Apexx\Afterpay\Model\Adminhtml\Source
 */
class CustomerType
{
    /**
     * Different customer type.
     */
    const CUSTOMER_CATEGORY = 'Person';

    public function toOptionArray()
    {
        return [
                    [
                        'value' => self::CUSTOMER_CATEGORY,
                        'label' => __('Person')
                    ]
        ];
    }
}
