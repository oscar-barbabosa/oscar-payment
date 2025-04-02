<?php
namespace Oscar\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session;

class Start extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $quote = $this->checkoutSession->getQuote();

        return $result->setData([
            'success' => true,
            'redirect_url' => $quote->getData('oscar_payment_url')
        ]);
    }
} 