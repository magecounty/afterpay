<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Apexx\Afterpay\Helper\Data as AfterPayHelper;
use Apexx\Base\Helper\Data as ApexxBaseHelper;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session as CheckoutSession;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var AfterPayHelper
     */
    protected  $afterpayHelper;

    /**
     * @var ApexxBaseHelper
     */
    protected  $apexxBaseHelper;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    public function __construct(
        ConfigInterface $config,
        AfterPayHelper $afterpayHelper,
        ApexxBaseHelper $apexxBaseHelper,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->config = $config;
        $this->afterpayHelper = $afterpayHelper;
        $this->apexxBaseHelper = $apexxBaseHelper;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */

    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $quoteCountryId = $this->checkoutSession->getQuote()->getBillingAddress()->getCountryId();
        $quoteCurrencyCode = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
        $allowCountry = $this->afterpayHelper->getAllowPaymentMethod($quoteCountryId, $quoteCurrencyCode);

        if (empty($allowCountry)) {
            throw new CommandException(__('Please select correct billing country'));
        }

        $shippingNetPrice = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingAmount();
        $shippingTaxAmount = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingTaxAmount();
        $shippingGrossPrice = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingInclTax();

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order= $payment->getOrder();
        $delivery = $order->getShippingAddress();
        $billing = $order->getBillingAddress();
        $total = $buildSubject['amount'];
       
        $subTotal = 0;
        foreach ($order->getItems() as $item) {
            $subTotal = $subTotal + $item->getPrice();
        }

        $formFields=[];
        $formFields['afterpay']['payment_type'] = $this->afterpayHelper->getCustomPaymentType();
        $formFields['afterpay']["gross_amount"] = ($total * 100);
        $formFields['afterpay']["net_amount"] = (($subTotal + $shippingNetPrice)  * 100);

        $requestData= [
            "account" => $allowCountry['account_id'],
            //"account" => 'eb82e77014d64f3a9b80342df2db62a0',
            //"organisation" => $this->apexxBaseHelper->getOrganizationId(),
            "currency" => $order->getCurrencyCode(),
            "merchant_reference" => 'JOURNEYBOX'.$order->getOrderIncrementId(),
            "capture_now" => false,
            "customer_ip" => $order->getRemoteIp(),
            "dynamic_descriptor" => $this->afterpayHelper->getDynamicDescriptor(),
            "user_agent" => $this->apexxBaseHelper->getUserAgent(),
            "shopper_interaction" => $this->afterpayHelper->getShopperInteraction(),
            "billing_address" => [
                "first_name" => $billing->getFirstname(),
                "last_name" => $billing->getLastname(),
                "email" => $billing->getEmail(),
                "address" => $billing->getStreetLine1().''.$billing->getStreetLine2(),
                "city" => $billing->getCity(),
                "state" => $billing->getRegionCode(),
                "postal_code" => $billing->getPostcode(),
                "country" => $billing->getCountryId()
            ]
        ];

        foreach ($order->getItems() as $item) {
            $formFields['afterpay']['items'][] = [
                'product_id' => $item->getProductId(),
                'item_description' => $item->getName(),
                'gross_unit_price' =>  ($item->getRowTotalInclTax() - $item->getDiscountAmount()) * 100,
                'net_unit_price' =>  ($item->getPrice() * 100),
                'quantity' => $item->getQtyOrdered(),
                'vat_percent' => $item->getTaxPercent(),
                'vat_amount' => ($item->getTaxAmount() * 100)
            ];
        }
         if($shippingNetPrice > 0)
        {
         $formFields['afterpay']['items'][] = 
            [
         'product_id'=> 'shipping',
                'group_id'=> 'shipping',
                'item_description'=> 'shipping',
                'gross_unit_price' => ($shippingGrossPrice * 100),
                'net_unit_price' =>  ($shippingNetPrice * 100),
                'quantity'=> 1,
                'vat_percent'=> 1,
                'vat_amount' => ($shippingTaxAmount * 100),
                'product_image_url'=> '',
                'product_url'=> '',
                'additional_information'=> ''
            ];
        }
        $formFields['afterpay']['customer']['email'] = $billing->getEmail();
        $formFields['afterpay']['customer']['type'] = $this->afterpayHelper->getAfterpayCustomerType();
        $formFields['afterpay']['delivery_customer']['type'] = $this->afterpayHelper->getAfterpayCustomerType();
        $formFields['afterpay']['delivery_customer']['first_name'] = $delivery->getFirstname();
        $formFields['afterpay']['delivery_customer']['last_name']=$delivery->getLastname();
        $formFields['afterpay']['delivery_customer']['address']=$delivery->getStreetLine1().''
            .$delivery->getStreetLine2();
        $formFields['afterpay']['delivery_customer']['city']=$delivery->getCity();
        $formFields['afterpay']['delivery_customer']['state']=$delivery->getRegionCode();
        $formFields['afterpay']['delivery_customer']['postal_code']=$delivery->getPostcode();
        $formFields['afterpay']['delivery_customer']['country']=$delivery->getCountryId();
        $requestData = array_merge($requestData, $formFields);

        return $requestData;
    }
}
