<?php

namespace NetworkInternational\NGenius\Controller\NGeniusOnline;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Class Redirect
 */
class Redirect extends Action
{
    protected const CARTPATH = "checkout/cart";

    /**
     * @var ResultFactory
     */
    protected $resultRedirect;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

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
        Session $checkoutSession,
        LayoutFactory $layoutFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->resultRedirect  = $resultRedirect;
        $this->checkoutSession = $checkoutSession;
        $this->layoutFactory   = $layoutFactory;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    /**
     * Redirects to ngenius payment portal
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $url = [];
        try {
            $block = $this->layoutFactory->create()->createBlock('NetworkInternational\NGenius\Block\Ngenius');
            $url   = $block->getPaymentUrl();
        } catch (\Exception $exception) {
            $url['exception'] = $exception;
        }

        $resultRedirectFactory = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        if (isset($url['url'])) {
            $resultRedirectFactory->setUrl($url['url']);
        } else {
            $exception = $url['exception'];
            $this->messageManager->addExceptionMessage($exception, $exception->getMessage());
            $resultRedirectFactory->setPath(self::CARTPATH);
            $order   = $this->checkoutSession->getLastRealOrder();
            $order->addCommentToStatusHistory($exception->getMessage());
            $order->setStatus('ngenius_failed');
            $order->setState(Order::STATE_CLOSED);
            $order->save();
            $this->restoreQuote();
        }

        return $resultRedirectFactory;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function restoreQuote()
    {
        $session = $this->checkoutSession;
        $order   = $session->getLastRealOrder();
        $quoteId = $order->getQuoteId();
        $quote   = $this->quoteRepository->get($quoteId);
        $quote->setIsActive(1)->setReservedOrderId(null);
        $this->quoteRepository->save($quote);
        $session->replaceQuote($quote)->unsLastRealOrderId();
    }
}
