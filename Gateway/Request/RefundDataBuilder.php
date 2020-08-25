<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Apexx\Base\Helper\Data as ApexxBaseHelper;
use Apexx\Afterpay\Helper\Data as AfterPayHelper;

/**
 * Class RefundDataBuilder
 * @package Apexx\Afterpay\Gateway\Request
 */
class RefundDataBuilder implements BuilderInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var ApexxBaseHelper
     */
    protected  $apexxBaseHelper;

    /**
     * @var AfterPayHelper
     */
    protected  $afterpayHelper;

    /**
     * RefundDataBuilder constructor.
     * @param SubjectReader $subjectReader
     * @param ApexxBaseHelper $apexxBaseHelper
     * @param AfterPayHelper $afterpayHelper
     */
    public function __construct(
        SubjectReader $subjectReader,
        ApexxBaseHelper $apexxBaseHelper,
        AfterPayHelper $afterpayHelper
    )
    {
        $this->subjectReader = $subjectReader;
        $this->apexxBaseHelper = $apexxBaseHelper;
        $this->afterpayHelper = $afterpayHelper;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();
        $total = $buildSubject['amount'];

        $shippingNetPrice = $order->getShippingAmount();
        $shippingTaxAmount = $order->getShippingTaxAmount();
        $shippingGrossPrice = $order->getShippingInclTax();

        //Get last transaction id for authorization
        $lastTransId = '';
        $lastTransId = $this->apexxBaseHelper->getHostedPayTxnId($order->getId());
        if ($lastTransId == '') {
            $lastTransId = $payment->getLastTransId();
        }

        $formFields=[];
        $requestData = [
            "transactionId" => $lastTransId,
            "creditnote_number" => 'creditnote'.mt_rand(1, 999999)
        ];

        foreach ($order->getItems() as $item) {
            $formFields['items'][] = [
                'product_id' => $item->getProductId(),
                'item_description' => $item->getName(),
                'gross_unit_price' =>  ($item->getRowTotalInclTax() - $item->getDiscountAmount()) * 100,
                'net_unit_price' =>  ($item->getPrice()) * 100,
                'quantity' => (int)$item->getQtyOrdered(),
                'vat_percent' => (int)$item->getTaxPercent(),
                'vat_amount' => ($item->getTaxAmount() * 100)
            ];
        }

        if ($shippingNetPrice > 0) {
            $formFields['items'][] = [
                'product_id'=> 'shipping',
                'group_id'=> 'shipping',
                'item_description'=> 'shipping',
                'gross_unit_price' => ($shippingGrossPrice * 100),
                'net_unit_price' =>  ($shippingNetPrice * 100),
                'quantity' => 1,
                'vat_percent' => 1,
                'vat_amount' => ($shippingTaxAmount * 100),
                'product_image_url'=> '',
                'product_url'=> '',
                'additional_information'=> ''
            ];
        }
        $requestData = array_merge($requestData, $formFields);
        return $requestData;
    }
}
