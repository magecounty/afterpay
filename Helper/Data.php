<?php
/**
 * Custom payment method in Magento 2
 * @category    AfterPay
 * @package     Apexx_Afterpay
 */
namespace Apexx\Afterpay\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Encryption\EncryptorInterface ;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Framework\Serialize\Serializer\Json as SerializeJson;
use \Magento\Framework\HTTP\Adapter\CurlFactory;
use \Magento\Framework\HTTP\Header as HttpHeader;
use \Magento\Sales\Model\OrderRepository;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use \Psr\Log\LoggerInterface;

/**
 * Class Data
 * @package Apexx\Afterpay\Helper
 */
class Data extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_PATH_CONFIG_PAYMENT_AFTERPAY       = 'payment/afterpay_gateway';
    const XML_PATH_PAYMENT_AFTERPAY       = 'payment/apexx_section/apexxpayment/afterpay_gateway';
    const XML_PATH_DYNAMIC_DESCRIPTOR     = '/dynamic_descriptor';
    const XML_PATH_SHOPPER_INTERACTION    = '/shopper_interaction';
    const XML_PATH_3DS_REQ                = '/three_d_status';
    const XML_PATH_CAPTURE_MODE           = '/capture_mode';
    const XML_PATH_PAYMENT_MODES          = '/payment_modes';
    const XML_PATH_PAYMENT_TYPE           = '/payment_type';
    const XML_COUNTRY_SPECIFIC_ACCOUNT_ID = '/countrycreditcard';
    const XML_PATH_CUSTOMER_TYPE          = '/customer_type';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var SerializeJson
     */
    protected $serializeJson;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var HttpHeader
     */
    protected $httpHeader;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Data constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param JsonFactory $resultJsonFactory
     * @param SerializeJson $serializeJson
     * @param CurlFactory $curlFactory
     * @param HttpHeader $httpHeader
     * @param OrderRepository $orderRepository
     * @param TransactionRepositoryInterface $transactionRepository
     * @param SearchCriteriaBuilder $searchBuilder
     * @param FilterBuilder $filterBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        JsonFactory $resultJsonFactory,
        SerializeJson $serializeJson,
        curlFactory $curlFactory,
        HttpHeader $httpHeader,
        OrderRepository $orderRepository,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder $searchBuilder,
        FilterBuilder $filterBuilder,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor ;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializeJson = $serializeJson;
        $this->curlFactory = $curlFactory;
        $this->httpHeader = $httpHeader;
        $this->orderRepository  = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->searchBuilder = $searchBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->logger = $logger;
    }

    /**
     * Get config value at the specified key
     *
     * @param string $key
     * @return mixed
     */
    public function getConfigPathValue($key)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CONFIG_PAYMENT_AFTERPAY . $key,
            ScopeInterface::SCOPE_STORE
        );
    }    


    /**
     * Get config value at the specified key
     *
     * @param string $key
     * @return mixed
     */
    public function getConfigValue($key)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_AFTERPAY . $key,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getDynamicDescriptor()
    {
        return $this->getConfigPathValue(self::XML_PATH_DYNAMIC_DESCRIPTOR);
    }


    /**
     * @param null $storeId
     * @return bool
     */
    public function getThreeDsRequired($storeId = null)
    {
        $three_ds_required = $this->scopeConfig->isSetFlag(self::XML_PATH_3DS_REQ,
            ScopeInterface::SCOPE_STORE, $storeId);

        return $three_ds_required; 
    }

    /**
     * @return mixed
     */
    public function getCaptureMode()
    {
        return $this->getConfigPathValue(self::XML_PATH_CAPTURE_MODE);
    }

    /**
     * @return mixed
     */
    public function getShopperInteraction()
    {
        return $this->getConfigPathValue(self::XML_PATH_SHOPPER_INTERACTION);
    }

    /**
     * @return string
     */
    public function getCustomPaymentType()
    {
        return $this->getConfigPathValue(self::XML_PATH_PAYMENT_TYPE);
    }

    /**
     * @return mixed
     */
    public function getAfterpayCustomerType()
    {
        return $this->getConfigPathValue(self::XML_PATH_CUSTOMER_TYPE);
    }

    /**
     * @param $countrycode
     * @return array
     */
    public function getAllowPaymentMethod($countrycode, $currencyCode) {

        $allowCountryList = $this->getConfigValue(self::XML_COUNTRY_SPECIFIC_ACCOUNT_ID);

        if (!empty($allowCountryList)) {
            $countryList = $this->serializeJson->unserialize($allowCountryList);
            $countryInfo = [];
            foreach ($countryList as $key => $value) {
                $countryCurrency = explode("_",$key);
                if (isset($countryCurrency)) {
	                if ($countryCurrency[0] == $countrycode && $countryCurrency[1] == $currencyCode) {
	                    $countryInfo['country_id'] = $countryCurrency[0];
	                    $countryInfo['currency_code'] = $countryCurrency[1];
	                    $countryInfo['account_id'] = $value;
	                }
	            }
            }

            return $countryInfo;
        }
    }
}
