<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Apexx\Afterpay\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'afterpay_gateway';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}
