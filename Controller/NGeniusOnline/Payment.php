<?php

namespace NetworkInternational\NGenius\Controller\NGeniusOnline;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\StoreManagerInterface;
use NetworkInternational\NGenius\Gateway\Config\Config;
use NetworkInternational\NGenius\Gateway\Http\Client\TransactionFetch;
use NetworkInternational\NGenius\Gateway\Http\TransferFactory;
use NetworkInternational\NGenius\Gateway\Request\TokenRequest;
use NetworkInternational\NGenius\Model\CoreFactory;
use NetworkInternational\NGenius\Setup\InstallData;
use Psr\Log\LoggerInterface;

/**
 * Class Payment
 */
class Payment implements HttpGetActionInterface
{
    /**
     * N-Genius states
     */
    public const NGENIUS_STARTED    = 'STARTED';
    public const NGENIUS_AUTHORISED = 'AUTHORISED';
    public const NGENIUS_PURCHASED  = 'PURCHASED';
    public const NGENIUS_CAPTURED   = 'CAPTURED';
    public const NGENIUS_FAILED     = 'FAILED';
    public const NGENIUS_VOIDED     = 'VOIDED';

    public const NGENIUS_EMBEDED = "_embedded";
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var TokenRequest
     */
    protected $tokenRequest;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var TransactionFetch
     */
    protected $transaction;

    /**
     * @var CoreFactory
     */
    protected $coreFactory;

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var ResultFactory
     */
    protected $resultRedirect;

    /**
     * @var error flag
     */
    protected $error = null;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var \NetworkInternational\NGenius\Setup\InstallData::getStatuses()
     */
    protected $orderStatus;

    /**
     * @var N-Genius state
     */
    protected $ngeniusState;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     *
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var PageFactory
     */
    protected PageFactory $pageFactory;

    /**
     * @var Product
     */
    private Product $productCollection;
    private string $errorMessage = 'There is an error with the payment';

    protected SerializerInterface $serializer;
    protected ManagerInterface $messageManager;
    protected Data $checkoutHelper;
    protected Builder $_transactionBuilder;
    protected OrderRepositoryInterface $orderRepository;

    /**
     * Payment constructor.
     *
     * @param ManagerInterface $messageManager
     * @param PageFactory $pageFactory
     * @param RequestInterface $request
     * @param Data $checkoutHelper
     * @param Config $config
     * @param TokenRequest $tokenRequest
     * @param StoreManagerInterface $storeManager
     * @param TransferFactory $transferFactory
     * @param TransactionFetch $transaction
     * @param CoreFactory $coreFactory
     * @param BuilderInterface $transactionBuilder
     * @param ResultFactory $resultRedirect
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param OrderSender $orderSender
     * @param OrderFactory $orderFactory
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param Product $productCollection
     * @param SerializerInterface $serializer
     * @param Builder $_transactionBuilder
     * @param OrderRepositoryInterface $orderRepository
     */
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
    ) {
        $this->request                = $request;
        $this->checkoutHelper         = $checkoutHelper;
        $this->pageFactory            = $pageFactory;
        $this->messageManager         = $messageManager;
        $this->config                 = $config;
        $this->tokenRequest           = $tokenRequest;
        $this->storeManager           = $storeManager;
        $this->transferFactory        = $transferFactory;
        $this->transaction            = $transaction;
        $this->coreFactory            = $coreFactory;
        $this->transactionBuilder     = $transactionBuilder;
        $this->resultRedirect         = $resultRedirect;
        $this->invoiceService         = $invoiceService;
        $this->transactionFactory     = $transactionFactory;
        $this->invoiceSender          = $invoiceSender;
        $this->orderSender            = $orderSender;
        $this->orderFactory           = $orderFactory;
        $this->logger                 = $logger;
        $this->orderStatus            = InstallData::getStatuses();
        $this->checkoutSession        = $checkoutSession;
        $this->productCollection      = $productCollection;
        $this->serializer             = $serializer;
        $this->_transactionBuilder    = $_transactionBuilder;
        $this->orderRepository        = $orderRepository;
    }

    /**
     * Default execute function.
     * @return URL
     */
    public function execute()
    {
        $orderRef              = $this->request->getParam('ref');
        $resultRedirectFactory = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);

        if ($orderRef) {
            $result = $this->getResponseAPI($orderRef);

            $embedded = self::NGENIUS_EMBEDED;
            if ($result && isset($result[$embedded]['payment']) && is_array($result[$embedded]['payment'])) {
                $action        = $result['action'] ?? '';
                $paymentResult = $result[$embedded]['payment'][0];
                $orderItem     = $this->fetchOrder('reference', $orderRef)->getFirstItem();
                $this->processOrder($paymentResult, $orderItem, $orderRef, $action);
            }
            if ($this->error) {
                $this->messageManager->addError(
                    __(
                        'Failed! There is an issue with your payment transaction. '
                        . $this->errorMessage
                    )
                );

                return $resultRedirectFactory->setPath('checkout/cart');
            } else {
                return $resultRedirectFactory->setPath('checkout/onepage/success');
            }
        } else {
            return $resultRedirectFactory->setPath('checkout');
        }
    }

    /**
     * Process Order - response from Payment Portal
     *
     * @param array $paymentResult
     * @param object $orderItem
     * @param string $orderRef
     * @param string $action
     *
     * @return null|boolean true
     */
    public function processOrder(array $paymentResult, object $orderItem, string $orderRef, string $action)
    {
        $dataTable   = [];
        $incrementId = $orderItem->getOrderId();

        if ($incrementId) {
            $paymentId = $this->getPaymentId($paymentResult);

            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            if ($order->getId()) {
                $dataTable               = $this->getCapturePayment(
                    $order,
                    $paymentResult,
                    $paymentId,
                    $action,
                    $dataTable
                );
                $dataTable['entity_id']  = $order->getId();
                $dataTable['payment_id'] = $paymentId;

                return $this->updateTable($dataTable, $orderItem);
            } else {
                $orderItem->setPaymentId($paymentId);
                $orderItem->setState($this->ngeniusState);
                $orderItem->setStatus($this->ngeniusState);
                $orderItem->save();
            }
        }
    }

    /**
     * @param $order
     * @param $paymentResult
     * @param $paymentId
     * @param $action
     * @param $dataTable
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function getCapturePayment($order, $paymentResult, $paymentId, $action, $dataTable)
    {
        if ($this->ngeniusState != self::NGENIUS_FAILED) {
            if ($this->ngeniusState != self::NGENIUS_STARTED) {
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus(Order::STATE_PROCESSING)->save();
                $this->orderSender->send($order, true);

                if ($action === "AUTH") {
                    $this->orderAuthorize($order, $paymentResult, $paymentId);
                } elseif ($action === "SALE" || $action === 'PURCHASE') {
                    $dataTable['captured_amt'] = $this->orderSale($order, $paymentResult, $paymentId);
                }
                $dataTable['status'] = $order->getStatus();
            } else {
                $dataTable['status'] = Order::STATE_PENDING_PAYMENT;
            }
        } else {
            // Authorisation has failed - cancel order
            // Reverse reserved stock
            $this->error        = true;
            $this->errorMessage = 'Result Code: ' . ($paymentResult['authResponse']['resultCode'] ?? 'FAILED')
                . ' Reason: ' . ($paymentResult['authResponse']['resultMessage'] ?? 'Unknown');
            $this->checkoutSession->restoreQuote();

            $payment = $order->getPayment();

            $formattedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

            $paymentData = [
                'Card Type'   => $paymentResult['paymentMethod']['name'] ?? '',
                'Card Number' => $paymentResult['paymentMethod']['pan'] ?? '',
                'Amount'      => $formattedPrice
            ];

            $trans       = $this->_transactionBuilder;

            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentId)
                ->setAdditionalInformation(
                    [Transaction::RAW_DETAILS => $paymentData]
                )
                ->setFailSafe(true)
                // Build method creates the transaction and returns the object
                ->build(TransactionInterface::TYPE_CAPTURE);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $this->errorMessage
            );

            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            $transaction->save()->getTransactionId();
            $this->updateInvoice($order, false);

            $payment->setAdditionalInformation(['raw_details_info' => json_encode($paymentResult)]);

            $storeId = $this->storeManager->getStore()->getId();

            if ($this->config->getCustomFailedOrderStatus($storeId) != null) {
                $status = $this->config->getCustomSuccessOrderStatus($storeId);
            } else {
                $status = Order::STATE_CLOSED;
            }

            if ($this->config->getCustomFailedOrderState($storeId) != null) {
                $state = $this->config->getCustomSuccessOrderState($storeId);
            } else {
                $state = Order::STATE_CLOSED;
            }

            $dataTable['status'] = $status;

            $order->setState($state);
            $order->setStatus($status);

            $order->cancel()->save();
            $order->addStatusHistoryComment('The payment on order has failed.')
                ->setIsCustomerNotified(false)->save();

        }

        return $dataTable;
    }

    /**
     * @param $paymentResult
     * Get payment id from payment response
     *
     * @return false|mixed|string
     */
    public function getPaymentId($paymentResult)
    {
        if (isset($paymentResult['_id'])) {
            $paymentIdArr = explode(':', $paymentResult['_id']);

            return end($paymentIdArr);
        }
    }

    /**
     * Order Authorize.
     *
     * @param object $order
     * @param array $paymentResult
     * @param string $paymentId
     *
     * @return null
     */
    public function orderAuthorize($order, $paymentResult, $paymentId)
    {
        if ($this->ngeniusState == self::NGENIUS_AUTHORISED) {
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentId);
            $payment->setTransactionId($paymentId);
            $payment->setAdditionalInformation(['paymentResult' => json_encode($paymentResult)]);
            $payment->setIsTransactionClosed(false);
            $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

            $paymentData = [
                'Card Type'   => $paymentResult['paymentMethod']['name'] ?? '',
                'Card Number' => $paymentResult['paymentMethod']['pan'] ?? '',
                'Amount'      => $formatedPrice
            ];

            $transactionBuilder = $this->transactionBuilder->setPayment($payment)
                                                           ->setOrder($order)
                                                           ->setTransactionId($paymentId)
                                                           ->setAdditionalInformation(
                                                               [Transaction::RAW_DETAILS => $paymentData]
                                                           )->setAdditionalInformation(
                    ['paymentResult' => json_encode($paymentResult)]
                )
                                                           ->setFailSafe(true)
                                                           ->build(
                                                               Transaction::TYPE_AUTH
                                                           );

            $payment->addTransactionCommentsToOrder($transactionBuilder, null);
            $payment->setParentTransactionId(null);
            $payment->save();

            $message = 'The payment has been approved and the authorized amount is ' . $formatedPrice;

            $status = Order::STATE_PROCESSING;

            $this->updateOrderStatus($order, $status, $message);
        }
    }

    /**
     * Order Sale.
     *
     * @param object $order
     * @param array $paymentResult
     * @param string $paymentId
     *
     * @return null|float
     * @throws \Exception
     */
    public function orderSale($order, $paymentResult, $paymentId)
    {
        if ($this->ngeniusState === self::NGENIUS_CAPTURED || $this->ngeniusState === self::NGENIUS_PURCHASED) {
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentId);
            $payment->setTransactionId($paymentId);
            $payment->setAdditionalInformation(['paymentResult' => json_encode($paymentResult)]);
            $payment->setIsTransactionClosed(false);
            $grandTotal    = $order->getGrandTotal();
            $formatedPrice = $order->getBaseCurrency()->formatTxt($grandTotal);

            $paymentData = [
                'Card Type'   => $paymentResult['paymentMethod']['name'] ?? '',
                'Card Number' => $paymentResult['paymentMethod']['pan'] ?? '',
                'Amount'      => $formatedPrice
            ];

            $transactionId = $paymentResult['reference'];

            $transactionBuilder = $this->transactionBuilder->setPayment($payment)
                                                           ->setOrder($order)
                                                           ->setTransactionId($transactionId)
                                                           ->setAdditionalInformation(
                                                               [Transaction::RAW_DETAILS => (array)$paymentData]
                                                           )
                                                           ->setAdditionalInformation(
                                                               ['paymentResult' => json_encode($paymentResult)]
                                                           )
                                                           ->setFailSafe(true)
                                                           ->build(
                                                               Transaction::TYPE_CAPTURE
                                                           );

            $payment->addTransactionCommentsToOrder($transactionBuilder, null);
            $payment->setParentTransactionId(null);
            $payment->save();

            $message = 'The payment has been approved and the captured amount is ' . $formatedPrice;

            if($order->canShip()) {
                $status = Order::STATE_PROCESSING;
            } else {
                $status = Order::STATE_COMPLETE;
            }

            $this->updateOrderStatus($order, $status, $message);

            $this->updateInvoice($order, true, $transactionId);

            return $grandTotal;
        }
    }

    /**
     * Update Invoice.
     *
     * @param object $order
     * @param bool $flag
     * @param string $transactionId
     *
     * @return null
     * @throws LocalizedException
     * @throws Exception
     */
    public function updateInvoice($order, $flag, $transactionId = null)
    {
        if ($order->hasInvoices()) {
            // gets here from a 'SALE' transaction
            if ($flag === false) {
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoice->cancel()->save();
                }
            } else {
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $this->doUpdateInvoice($invoice, $transactionId, $order);
                }
            }
        } elseif ($flag) {
            // Create invoice - gets here from a 'PURCHASE' transaction
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $payment = $order->getPayment();
            $payment->setCreatedInvoice($invoice);
            $order->setPayment($payment);
            $this->doUpdateInvoice($invoice, $transactionId, $order);
        }
    }

    /**
     * Update Table.
     *
     * @param array $data
     * @param object $orderItem
     *
     * @return bool true
     */
    public function updateTable(array $data, $orderItem)
    {
        $orderItem->setEntityId($data['entity_id']);
        $orderItem->setState($this->ngeniusState);
        $orderItem->setStatus($data['status']);
        $orderItem->setPaymentId($data['payment_id']);
        if (isset($data['captured_amt'])) {
            $orderItem->setCapturedAmt($data['captured_amt']);
        }
        $orderItem->save();

        return true;
    }

    /**
     * Fetch  order details.
     *
     * @param string $orderRef
     *
     * @return array
     */
    public function getResponseAPI($orderRef)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $request = [
            'token'   => $this->tokenRequest->getAccessToken($storeId),
            'request' => [
                'data'   => [],
                'method' => \Laminas\Http\Request::METHOD_GET,
                'uri'    => $this->config->getFetchRequestURL($orderRef, $storeId)
            ]
        ];
        $result = $this->transaction->placeRequest($request);

        return $this->resultValidator($result);
    }

    /**
     * Validate API response.
     *
     * @param array $result
     *
     */
    public function resultValidator($result)
    {
        if (isset($result['errors']) && is_array($result['errors'])) {
            $this->error = true;

            return false;
        } else {
            $this->error        = false;
            $this->ngeniusState = $result[self::NGENIUS_EMBEDED]['payment'][0]['state'] ?? '';

            return $result;
        }
    }

    /**
     * Fetch order details.
     *
     * @param string $key
     * @param string $value
     *
     * @return object
     */
    public function fetchOrder($key, $value)
    {
        return $this->coreFactory->create()->getCollection()->addFieldToFilter($key, $value);
    }

    /**
     * Cron Task.
     *
     * @return null
     */
    public function cronTask()
    {
        $orderItems = $this->fetchOrder('state', self::NGENIUS_STARTED)->addFieldToFilter(
            'payment_id',
            null
        )->addFieldToFilter('created_at', ['lteq' => date('Y-m-d H:i:s', strtotime('-1 hour'))])->setOrder(
            'nid',
            'DESC'
        );
        if ($orderItems) {
            foreach ($orderItems as $orderItem) {
                $orderRef = $orderItem->getReference();
                $result   = $this->getResponseAPI($orderRef);
                $embedded = self::NGENIUS_EMBEDED;
                if ($result && isset($result[$embedded]['payment']) && is_array($result[$embedded]['payment'])) {
                    $action        = $result['action'] ?? '';
                    $paymentResult = $result[$embedded]['payment'][0];
                    $this->processOrder($paymentResult, $orderItem, $orderRef, $action);
                }
            }
        }
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface|\Magento\Sales\Model\Order\Invoice $invoice
     * @param string|null $transactionId
     * @param object $order
     *
     * @return void
     * @throws Exception
     */
    public function doUpdateInvoice(
        InvoiceInterface|Invoice $invoice,
        ?string $transactionId,
        object $order
    ): void {
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->setTransactionId($transactionId);
        $invoice->pay()->save();
        $transactionSave = $this->transactionFactory->create()->addObject($invoice)->addObject(
            $invoice->getOrder()
        );
        $transactionSave->save();

        if ($this->config->getInvoiceSend()) {
            try {
                $this->invoiceSender->send($invoice);
                $order->addStatusHistoryComment(
                    __('Notified the customer about invoice #%1.', $invoice->getIncrementId())
                )
                    ->setIsCustomerNotified(true)->save();
            } catch (Exception $e) {
                $this->messageManager->addError(__('We can\'t send the invoice email right now.'));
            }
        }

    }

    private function updateOrderStatus($order, $status, $message): void
    {
        //Check For Custom Order Status on Payment Complete
        $storeId = $this->storeManager->getStore()->getId();

        if ($this->config->getCustomSuccessOrderStatus($storeId) != null) {
            $status = $this->config->getCustomSuccessOrderStatus($storeId);
        }

        if ($this->config->getCustomSuccessOrderState($storeId) != null) {
            $order->setState($this->config->getCustomSuccessOrderState($storeId));
        }

        $order->addStatusToHistory($status, $message, true);
        $order->save();
    }
}
