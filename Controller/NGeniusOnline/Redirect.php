<?php

namespace NetworkInternational\NGenius\Controller\NGeniusOnline;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session;

/**
 * Class Redirect
 */
class Redirect extends \Magento\Framework\App\Action\Action
{

    /**
     * @var ResultFactory
     */
    protected $resultRedirect;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param ResultFactory $resultRedirect
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        ResultFactory $resultRedirect,
        Session $checkoutSession
    ) {
        $this->resultRedirect = $resultRedirect;
        $this->checkoutSession = $checkoutSession;
        return parent::__construct($context);
    }

    /**
     * Default execute function.
     *
     * @return ResultFactory
     */
    public function execute()
    {
        $url = $this->checkoutSession->getPaymentURL();
        $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        if ($url) {
            $resultRedirect->setUrl($url);
        } else {
            $resultRedirect->setPath('checkout');
        }
        $this->checkoutSession->unsPaymentURL();
        return $resultRedirect;
    }
}
