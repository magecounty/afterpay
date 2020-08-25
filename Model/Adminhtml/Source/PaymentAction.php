<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Model\Adminhtml\Source;

/**
 * Class PaymentAction
 * @package Apexx\Afterpay\Model\Adminhtml\Source
 */
class PaymentAction
{
    /**
     * Different payment actions.
     */
    const ACTION_AUTHORIZE = 'authorize';

   // const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    public function toOptionArray()
    {
        return [
                    // [
                    //     'value' => self::ACTION_AUTHORIZE_CAPTURE,
                    //     'label' => __('Authorize and Capture')
                    // ],
                    [
                        'value' => self::ACTION_AUTHORIZE,
                        'label' => __('Authorize Only')
                    ],
        ];
    }
}
