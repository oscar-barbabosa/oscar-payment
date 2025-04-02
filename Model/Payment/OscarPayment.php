<?php
namespace Oscar\Payment\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class OscarPayment extends AbstractMethod
{
    const CODE = 'oscar_payment';

    protected $_code = 'oscar_payment';
    protected $_isGateway = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = false;
    protected $_isInitializeNeeded = true;
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

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param LoggerInterface $psrLogger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        LoggerInterface $psrLogger,
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
        $this->urlBuilder = $urlBuilder;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $psrLogger;
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->logger->debug('OscarPayment: Initializing payment method');
        
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $this->getInfoInstance();
        $quote = $payment->getQuote();

        $this->logger->debug('OscarPayment: Quote details', [
            'quote_id' => $quote->getId(),
            'payment_method' => $quote->getPayment()->getMethod(),
            'current_payment_url' => $quote->getData('oscar_payment_url'),
            'is_active' => $quote->getIsActive(),
            'reserved_order_id' => $quote->getReservedOrderId()
        ]);

        try {
            $this->logger->debug('OscarPayment: Creating payment in external API', [
                'quote_id' => $quote->getId()
            ]);
            
            // Call external API to create payment
            $response = $this->createPaymentInExternalApi($quote);
            
            $this->logger->debug('OscarPayment: API response received', [
                'payment_id' => $response['payment_id'],
                'payment_url' => $response['payment_url']
            ]);
            
            // Store payment data in quote for later use
            $quote->setData('oscar_payment_id', $response['payment_id']);
            $quote->setData('oscar_payment_url', $response['payment_url']);
            
            $this->logger->debug('OscarPayment: Before saving quote', [
                'payment_id' => $quote->getData('oscar_payment_id'),
                'payment_url' => $quote->getData('oscar_payment_url')
            ]);
            
            // Save the quote and reload it to ensure data is persisted
            $quote->save();
            $quote = $this->quoteFactory->create()->load($quote->getId());
            
            $this->logger->debug('OscarPayment: After saving and reloading quote', [
                'payment_id' => $quote->getData('oscar_payment_id'),
                'payment_url' => $quote->getData('oscar_payment_url')
            ]);

            // Set state object as pending
            $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $stateObject->setStatus('pending_payment');
            $stateObject->setIsNotified(false);

            // Store the quote ID for later use
            $this->checkoutSession->setOscarPaymentQuoteId($quote->getId());
            
            // Make sure the quote is not marked as submitted
            $quote->setIsActive(true);
            $quote->setReservedOrderId(null);
            $quote->save();

            $this->logger->debug('OscarPayment: Initialization complete');
            return $this;
        } catch (\Exception $e) {
            $this->logger->error('OscarPayment: Error during initialization', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
        $this->logger->debug('OscarPayment: Getting redirect URL');
        
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $this->getInfoInstance();
        $quote = $payment->getQuote();
        
        if (!$quote || !$quote->getId()) {
            $this->logger->error('OscarPayment: No quote found when getting redirect URL');
            return '';
        }

        $this->logger->debug('OscarPayment: Quote found', [
            'quote_id' => $quote->getId(),
            'payment_method' => $quote->getPayment()->getMethod()
        ]);

        $redirectUrl = $quote->getData('oscar_payment_url');
        if (!$redirectUrl) {
            $this->logger->debug('OscarPayment: No payment URL found, creating new payment');
            
            // Call external API to create payment
            $response = $this->createPaymentInExternalApi($quote);
            
            $this->logger->debug('OscarPayment: API response received', [
                'payment_id' => $response['payment_id'],
                'payment_url' => $response['payment_url']
            ]);
            
            // Store payment data in quote for later use
            $quote->setData('oscar_payment_id', $response['payment_id']);
            $quote->setData('oscar_payment_url', $response['payment_url']);
            
            $this->logger->debug('OscarPayment: Before saving quote', [
                'payment_id' => $quote->getData('oscar_payment_id'),
                'payment_url' => $quote->getData('oscar_payment_url')
            ]);
            
            // Save the quote and reload it to ensure data is persisted
            $quote->save();
            $quote = $this->quoteFactory->create()->load($quote->getId());
            
            $this->logger->debug('OscarPayment: After saving and reloading quote', [
                'payment_id' => $quote->getData('oscar_payment_id'),
                'payment_url' => $quote->getData('oscar_payment_url')
            ]);

            $redirectUrl = $quote->getData('oscar_payment_url');
        }

        $this->logger->debug('OscarPayment: Found payment URL', ['url' => $redirectUrl]);
        return $redirectUrl;
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

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) && $this->isActive();
    }

    /**
     * Validate payment method information
     *
     * @return $this
     * @throws LocalizedException
     */
    public function validate()
    {
        parent::validate();
        return $this;
    }
} 