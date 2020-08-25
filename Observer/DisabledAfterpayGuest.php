<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session As CheckoutSession;
use Apexx\Afterpay\Helper\Data As AfterpayHelper;

/**
 * Class DisabledAfterpayGuest
 * @package Apexx\Afterpay\Observer
 */
class DisabledAfterpayGuest implements ObserverInterface
{
    /**
     * @var Session
     */
	protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var AfterpayHelper
     */
    protected $afterpayHelper;

    /**
     * DisabledAfterpayGuest constructor.
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CartRepositoryInterface $quoteRepository
     * @param CheckoutSession $checkoutSession
     * @param AfterpayHelper $afterpayHelper
     */
	public function __construct(
	    Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        AfterpayHelper $afterpayHelper
    ) {
		$this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->afterpayHelper = $afterpayHelper;
	}

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
	public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $paymentMethod = $observer->getEvent()->getMethodInstance()->getCode();
        $result = $observer->getEvent()->getResult();

        if ($this->customerSession->isLoggedIn()) {
            $quoteCountryId = $this->checkoutSession->getQuote()->getShippingAddress()->getCountryId();
            $quoteCurrencyCode = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
            $allowCountry = $this->afterpayHelper->getAllowPaymentMethod($quoteCountryId, $quoteCurrencyCode);

            if ($paymentMethod == 'afterpay_gateway') {
                if (!empty($allowCountry)) {
                    $result->setData('is_available', true);
                    return;
                } else {
                    $result->setData('is_available', false);
                    return;
                }
            }
        } else {
            if ($paymentMethod == 'afterpay_gateway') {
                $result->setData('is_available', false);
                return;
            }
        }
    }
}
