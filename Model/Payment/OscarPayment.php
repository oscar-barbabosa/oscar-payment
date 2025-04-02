<?php
namespace Oscar\Payment\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

class OscarPayment extends AbstractMethod implements GatewayInterface
{
    const CODE = 'oscar_payment';

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_isInitializeNeeded = true;

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
     * Initialize payment method
     *
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $quote = $this->quoteFactory->create()->load($order->getQuoteId());

        try {
            // Call external API to create payment
            $response = $this->createPaymentInExternalApi($quote);
            
            // Store payment data in quote for later use
            $quote->setData('oscar_payment_id', $response['payment_id']);
            $quote->setData('oscar_payment_url', $response['payment_url']);
            $quote->save();

            // Set order state to pending
            $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING);
            $stateObject->setStatus('pending');
            $stateObject->setIsNotified(false);

        } catch (\Exception $e) {
            throw new LocalizedException(__('Error creating payment: %1', $e->getMessage()));
        }

        return $this;
    }

    /**
     * Create payment in external API
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function createPaymentInExternalApi($quote)
    {
        // TODO: Implement actual API call
        // This is just an example structure
        $apiUrl = $this->getConfigData('api_url');
        $apiKey = $this->getConfigData('api_key');

        $data = [
            'amount' => $quote->getGrandTotal(),
            'currency' => $quote->getBaseCurrencyCode(),
            'order_id' => $quote->getId(),
            'success_url' => $this->urlBuilder->getUrl('oscar_payment/payment/success'),
            'cancel_url' => $this->urlBuilder->getUrl('oscar_payment/payment/cancel'),
            'webhook_url' => $this->urlBuilder->getUrl('oscar_payment/webhook/index'),
        ];

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Bearer ' . $apiKey);
        $this->curl->post($apiUrl, $this->json->serialize($data));

        $response = $this->json->unserialize($this->curl->getBody());

        if (!isset($response['payment_id']) || !isset($response['payment_url'])) {
            throw new LocalizedException(__('Invalid response from payment API'));
        }

        return $response;
    }

    /**
     * Get payment redirect URL
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $quote = $this->checkoutSession->getQuote();
        return $quote->getData('oscar_payment_url');
    }

    /**
     * Post request to gateway and return response
     *
     * @param DataObject $request
     * @return DataObject
     */
    public function postRequest(DataObject $request, $client)
    {
        // This method is required by the interface but we don't use it
        // as we handle the API call in createPaymentInExternalApi
        return $request;
    }
} 