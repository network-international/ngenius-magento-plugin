<?php

namespace NetworkInternational\NGenius\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;

/**
 * NGenius checkout info observer implementation
 *
 * Class Standard
 */
class Standard implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Standard constructor.
     *
     * @param Session $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->messageManager  = $messageManager;
    }

    /**
     * Execute.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $redirectionUrl = $this->checkoutSession->getPaymentURL();
        if ($redirectionUrl) {
            $message = 'Go to <a href="' . $redirectionUrl . '" target="_blank">payment page</a> to do the transaction';
            $this->messageManager->addNotice(__($message));
        }
    }
}
