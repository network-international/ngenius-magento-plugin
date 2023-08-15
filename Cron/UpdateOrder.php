<?php

namespace NetworkInternational\NGenius\Cron;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\StoreManagerInterface;
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Gateway\Http\Client\TransactionFetch;
use NetworkInternational\NGenius\Gateway\Http\TransferFactory;
use NetworkInternational\NGenius\Gateway\Request\TokenRequest;
use NetworkInternational\NGenius\Model\CoreFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use NetworkInternational\NGenius\Controller\NGeniusOnline\Payment;

/**
 * Class UpdateOrder
 */
class UpdateOrder
{
    private Payment $ngeniusPaymentTools;
    private State $state;
    private LoggerInterface $logger;

    public function __construct(
        ManagerInterface $messageManager,
        PageFactory $pageFactory,
        RequestInterface $request,
        Data $checkoutHelper,
        Config $config,
        TokenRequest $tokenRequest,
        StoreManagerInterface $storeManager,
        TransferFactory $transferFactory,
        TransactionFetch $transaction,
        CoreFactory $coreFactory,
        BuilderInterface $transactionBuilder,
        ResultFactory $resultRedirect,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender,
        OrderSender $orderSender,
        OrderFactory $orderFactory,
        LoggerInterface $logger,
        Session $checkoutSession,
        Product $productCollection,
        SerializerInterface $serializer,
        Builder $_transactionBuilder,
        OrderRepositoryInterface $orderRepository,
        State $state
    ) {
        $this->ngeniusPaymentTools = new Payment(
            $messageManager,
            $pageFactory,
            $request,
            $checkoutHelper,
            $config,
            $tokenRequest,
            $storeManager,
            $transferFactory,
            $transaction,
            $coreFactory,
            $transactionBuilder,
            $resultRedirect,
            $invoiceService,
            $transactionFactory,
            $invoiceSender,
            $orderSender,
            $orderFactory,
            $logger,
            $checkoutSession,
            $productCollection,
            $serializer,
            $_transactionBuilder,
            $orderRepository
        );
        $this->state = $state;
        $this->logger = $logger;
    }
    /**
     * Default execute function.
     *
     * @return null
     */
    public function execute()
    {
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
                function () {
                    try {
                        $this->logger->info('N-GENIUS cron started');
                        $this->ngeniusPaymentTools->cronTask();
                        $this->logger->info('N-GENIUS cron ended');
                    } catch (\Exception $ex) {
                        $this->logger->error($ex->getMessage());
                    }
                }
        );
    }
}
