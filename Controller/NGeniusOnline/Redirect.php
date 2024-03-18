<?php

namespace NetworkInternational\NGenius\Controller\NGeniusOnline;

use Exception;
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
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Block\Ngenius;

/**
 * Class Redirect
 *
 * The Redirect Controller responsible for sending the customer to the NGenius Payment Page
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
    protected LayoutFactory $layoutFactory;
    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var Config
     */
    private Config $config;

    /**
     * Redirect constructor.
     *
     * @param ResultFactory $resultRedirect
     * @param Session $checkoutSession
     * @param LayoutFactory $layoutFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     */
    public function __construct(
        ResultFactory $resultRedirect,
        Session $checkoutSession,
        LayoutFactory $layoutFactory,
        CartRepositoryInterface $quoteRepository,
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        Config $config,
    ) {
        $this->resultRedirect  = $resultRedirect;
        $this->checkoutSession = $checkoutSession;
        $this->layoutFactory   = $layoutFactory;
        $this->quoteRepository = $quoteRepository;
        $this->messageManager  = $messageManager;
        $this->scopeConfig     = $scopeConfig;
        $this->config          = $config;
    }

    /**
     * Redirects to ngenius payment portal
     *
     * @return ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        $storeId = $order->getStoreId();

        $ngeniusPaymentAction = $this->config->getPaymentAction($storeId);

        $url = [];
        try {
            $block = $this->layoutFactory->create()->createBlock(Ngenius::class);
            $url   = $block->getPaymentUrl($ngeniusPaymentAction);
        } catch (Exception $exception) {
            $url['exception'] = $exception;
        }

        $resultRedirectFactory = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        $initialStatus         = $this->config->getInitialOrderStatus($storeId);
        $order                 = $this->checkoutSession->getLastRealOrder();
        $order->setState($initialStatus);
        $order->setStatus($initialStatus);
        $order->addStatusHistoryComment(
            __('Set configured "Status of new order".')
        );
        $order->save();
        if (isset($url['url'])) {
            $resultRedirectFactory->setUrl($url['url']);
        } else {
            $exception = $url['exception'];
            $this->messageManager->addExceptionMessage($exception, $exception->getMessage());
            $resultRedirectFactory->setPath(self::CARTPATH);
            $order = $this->checkoutSession->getLastRealOrder();
            $order->addCommentToStatusHistory($exception->getMessage());
            $order->setStatus('ngenius_failed');
            $order->setState(Order::STATE_CLOSED);
            $order->save();
            $this->restoreQuote();
        }

        return $resultRedirectFactory;
    }

    /**
     * Cart restore
     *
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
