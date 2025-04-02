<?php
namespace Oscar\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Redirect extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        
        if (!$quote || !$quote->getId()) {
            return $this->_redirect('checkout/cart');
        }

        $redirectUrl = $quote->getData('oscar_payment_url');
        
        if (!$redirectUrl) {
            return $this->_redirect('checkout/cart');
        }

        // Clear the current session to prevent order creation
        $this->checkoutSession->clearQuote();
        $this->checkoutSession->clearStorage();
        
        // Keep only the quote ID for later use
        $this->checkoutSession->setOscarPaymentQuoteId($quote->getId());

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setUrl($redirectUrl);
    }
} 