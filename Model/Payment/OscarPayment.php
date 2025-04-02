<?php
namespace Oscar\Payment\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

class OscarPayment extends AbstractMethod
{
    const CODE = 'oscar_payment';

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = false;
    protected $_isInitializeNeeded = false;
    protected $_canOrder = true;
    protected $_isOffline = false;
    protected $_canCapturePartial = false;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        Curl $curl,
        Json $json,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
        $this->curl = $curl;
        $this->json = $json;
        $this->urlBuilder = $urlBuilder;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $quote = $this->checkoutSession->getQuote();

        try {
            // Call external API to create payment
            $response = $this->createPaymentInExternalApi($quote);
            
            // Store payment data in quote for later use
            $quote->setData('oscar_payment_id', $response['payment_id']);
            $quote->setData('oscar_payment_url', $response['payment_url']);
            $quote->save();

            // Cancel the current order process
            throw new LocalizedException(__('redirect'));

        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Create payment in external API
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function createPaymentInExternalApi($quote)
    {
        // Simulate external API response
        return [
            'payment_id' => 'test_' . $quote->getId() . '_' . time(),
            'payment_url' => 'https://www.google.com' // For testing purposes
        ];
    }

    /**
     * Get redirect URL
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('oscar_payment/payment/redirect');
    }

    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool) $this->getConfigData('active', $storeId);
    }
} 