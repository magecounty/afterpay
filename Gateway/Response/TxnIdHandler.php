<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Gateway\Response;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TxnIdHandler implements HandlerInterface
{
    const TXN_ID = '_id';

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($response) || !is_array($response)) {
            throw new ClientException(__('Response does not exist'));
        }

        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new ClientException(__('Payment data object should be provided'));

        }

        if (isset($response['status'])) {
            if ($response['status'] == 'AUTHORISED') {
                /** @var PaymentDataObjectInterface $paymentDO */
                $paymentDO = $handlingSubject['payment'];
                $payment = $paymentDO->getPayment();

                /** @var $payment \Magento\Sales\Model\Order\Payment */
                $payment->setTransactionId($response[self::TXN_ID]);
                $payment->setIsTransactionClosed(false);
                $payment->setTransactionAdditionalInfo('raw_details_info',$response);
            }  elseif ($response['status'] == 'FAILED') {
                if ($response['errors']) {
                    if (isset($response['errors'][0]['error_message'])) {
                        throw new ClientException(__($response['errors'][0]['error_message']));
                    } else {
                        if (isset($response['reason_message'])) {
                            throw new ClientException(__($response['reason_message']));
                        }
                    }
                }
                throw new ClientException(__('A server error stopped your order from being placed.'));
            } elseif ($response['status'] == 'DECLINED') {
                if ($response['errors']) {
                    if (isset($response['errors'][0]['error_message'])) {
                        throw new ClientException(__($response['errors'][0]['error_message']));
                    } else {
                        if (isset($response['reason_message'])) {
                            throw new ClientException(__($response['reason_message']));
                        }
                    }
                }
                throw new ClientException(__('A server error stopped your order from being placed.'));
            }
        } elseif (isset($response['message'])) {
            throw new ClientException(__($response['message']));
        } else {
            throw new ClientException(__('A server error stopped your order from being placed.'));
        }
    }
}
