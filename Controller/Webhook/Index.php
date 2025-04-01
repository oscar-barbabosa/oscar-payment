<?php
namespace OscarPayment\Payment\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Index extends Action implements CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderManagementInterface $orderManagement,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderManagement = $orderManagement;
        $this->orderFactory = $orderFactory;
        $this->quoteFactory = $quoteFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $request = $this->getRequest();
            $paymentId = $request->getParam('payment_id');
            $status = $request->getParam('status');

            // Find quote by payment_id
            $quote = $this->quoteFactory->create()
                ->getCollection()
                ->addFieldToFilter('oscar_payment_id', $paymentId)
                ->getFirstItem();

            if (!$quote->getId()) {
                throw new LocalizedException(__('Quote not found for payment ID: %1', $paymentId));
            }

            // Create order from quote
            $order = $this->orderFactory->create();
            $order->loadByIncrementId($quote->getReservedOrderId());

            if (!$order->getId()) {
                throw new LocalizedException(__('Order not found for quote ID: %1', $quote->getId()));
            }

            // Update order status based on payment status
            if ($status === 'approved') {
                $order->setState(Order::STATE_PROCESSING)
                    ->setStatus(Order::STATE_PROCESSING);
            } else {
                $order->setState(Order::STATE_CANCELED)
                    ->setStatus(Order::STATE_CANCELED);
            }

            $order->save();

            return $result->setData([
                'success' => true,
                'message' => 'Order status updated successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Webhook error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ])->setHttpResponseCode(400);
        }
    }
} 