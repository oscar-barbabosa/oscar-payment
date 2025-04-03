<?php
namespace Oscar\Payment\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class PreventOrderCreation
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
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Session $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int
     * @throws LocalizedException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $this->logger->debug('OscarPayment: Checking payment method', [
            'method' => $paymentMethod->getMethod()
        ]);

        if ($paymentMethod->getMethod() === 'oscar_payment') {
            $this->logger->debug('OscarPayment: Preventing order creation');
            throw new LocalizedException(__('Please complete the payment first'));
        }

        return $proceed($cartId, $paymentMethod, $billingAddress);
    }
} 