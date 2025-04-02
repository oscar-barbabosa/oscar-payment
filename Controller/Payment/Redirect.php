<?php
namespace Oscar\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Redirect extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logger->debug('OscarPayment Redirect: Starting redirect process');
        
        $quote = $this->checkoutSession->getQuote();
        
        if (!$quote || !$quote->getId()) {
            $this->logger->error('OscarPayment Redirect: No quote found');
            return $this->_redirect('checkout/cart');
        }

        $this->logger->debug('OscarPayment Redirect: Quote found', [
            'quote_id' => $quote->getId()
        ]);

        $redirectUrl = $quote->getData('oscar_payment_url');
        
        if (!$redirectUrl) {
            $this->logger->error('OscarPayment Redirect: No payment URL found in quote');
            return $this->_redirect('checkout/cart');
        }

        $this->logger->debug('OscarPayment Redirect: Payment URL found', [
            'url' => $redirectUrl
        ]);

        // Store the quote ID for later use
        $this->checkoutSession->setOscarPaymentQuoteId($quote->getId());
        
        // Make sure the quote is not marked as submitted
        $quote->setIsActive(true);
        $quote->setReservedOrderId(null);
        $quote->save();

        $this->logger->debug('OscarPayment Redirect: Quote updated and saved');

        // Clear the session to prevent order creation
        $this->checkoutSession->clearQuote();
        $this->checkoutSession->clearStorage();

        $this->logger->debug('OscarPayment Redirect: Session cleared, redirecting to payment URL');

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setUrl($redirectUrl);
    }
} 