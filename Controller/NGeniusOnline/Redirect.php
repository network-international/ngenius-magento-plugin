<?php

namespace NetworkInternational\NGenius\Controller\NGeniusOnline;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Class Redirect
 */
class Redirect implements HttpGetActionInterface
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
    private ManagerInterface $messageManager;
    private ScopeConfigInterface $scopeConfig;

    /**
     * Redirect constructor.
     *
     * @param ResultFactory $resultRedirect
     * @param Session $checkoutSession
     * @param LayoutFactory $layoutFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        ResultFactory $resultRedirect,
        Session $checkoutSession,
        LayoutFactory $layoutFactory,
        CartRepositoryInterface $quoteRepository,
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resultRedirect  = $resultRedirect;
        $this->checkoutSession = $checkoutSession;
        $this->layoutFactory   = $layoutFactory;
        $this->quoteRepository = $quoteRepository;
        $this->messageManager = $messageManager;
        $this->scopeConfig    = $scopeConfig;
    }

    /**
     * Redirects to ngenius payment portal
     *
     * @return ResultInterface
     * @throws NoSuchEntityException
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
        $initialStatus = $this->scopeConfig->getValue('payment/ngeniusonline/ngenius_initial_order_status');

        $resultRedirectFactory = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        $order   = $this->checkoutSession->getLastRealOrder();
        $order->setState($initialStatus);
        $order->setStatus($initialStatus);
        $order->save();
        if (isset($url['url'])) {
            $resultRedirectFactory->setUrl($url['url']);
        } else {
            $exception = $url['exception'];
            $this->messageManager->addExceptionMessage($exception, $exception->getMessage());
            $resultRedirectFactory->setPath(self::CARTPATH);
            $order->addCommentToStatusHistory($exception->getMessage());
            $order->setStatus('ngenius_failed');
            $order->setState(Order::STATE_CLOSED);
            $order->save();
            $this->restoreQuote();
        }

        return $resultRedirectFactory;
    }

    /**
     * @throws NoSuchEntityException
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
