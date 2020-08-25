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
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;

/**
 * Class CaptureDataBuilder
 * @package Apexx\Afterpay\Gateway\Request
 */
class CaptureDataBuilder implements BuilderInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Create capture request
     *
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
        /**
         * @var Invoice $invoice
         */
        $invoice = $payment->getOrder()->getInvoiceCollection()->getLastItem();

        $shippingNetPrice = $order->getShippingAmount();
        $shippingTaxAmount = $order->getShippingTaxAmount();
        $shippingGrossPrice = $order->getShippingInclTax();

        $formFields=[];
        $requestData = [
            "transactionId" => $payment->getLastTransId(),
            "gross_amount" => ($total * 100),
            "net_amount" => (($order->getSubtotal()+$shippingNetPrice) * 100),
            "invoice_number" => 'invoice'.$order->getIncrementId(),
            "invoice_date" => date('Y-m-d'),
            "override_merchant_reference" => true
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
            $formFields['items'][] =
            [
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
