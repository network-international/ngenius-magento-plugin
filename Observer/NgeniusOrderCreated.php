<?php

namespace NetworkInternational\NGenius\Observer;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use NetworkInternational\NGenius\Model\CoreFactory;
use NetworkInternational\NGenius\Service\NgeniusApiService;
use NetworkInternational\NGenius\Service\OrderDataFormatter;
use NetworkInternational\NGenius\Service\OrderStatusService;
use Psr\Log\LoggerInterface;

class NgeniusOrderCreated implements ObserverInterface
{
    private OrderStatusService $orderStatusService;
    private NgeniusApiService $ngeniusApiService;
    private OrderDataFormatter $orderDataFormatter;
    private CoreFactory $ngeniusCoreFactory;
    private LoggerInterface $logger;
    private State $appState;

    /**
     * @param OrderStatusService $orderStatusService
     * @param NgeniusApiService $ngeniusApiService
     * @param OrderDataFormatter $orderDataFormatter
     * @param CoreFactory $coreFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderStatusService $orderStatusService,
        NgeniusApiService $ngeniusApiService,
        OrderDataFormatter $orderDataFormatter,
        CoreFactory $coreFactory,
        LoggerInterface $logger,
        State $appState
    ) {
        $this->orderStatusService = $orderStatusService;
        $this->ngeniusApiService  = $ngeniusApiService;
        $this->orderDataFormatter = $orderDataFormatter;
        $this->ngeniusCoreFactory = $coreFactory;
        $this->logger             = $logger;
        $this->appState           = $appState;
    }

    /**
     * @throws CouldNotSaveException
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$this->orderStatusService->isNgeniusPayment($order) || $this->isFrontendOrder()) {
            return;
        }

        $this->orderStatusService->setInitialStatus($order);

        $invoiceData = $this->orderDataFormatter->format($order);

        try {
            $response = $this->ngeniusApiService->createInvoice($order, $invoiceData);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        if (!$this->ngeniusApiService->isValidResponse($response)) {
            $errorMessage = 'Invalid response from Ngenius: ' . json_encode($response);
            $this->logger->error($errorMessage);
            throw new CouldNotSaveException(__($errorMessage));
        }

        $this->saveNgeniusOrder($order, $response);
    }

    private function isFrontendOrder(): bool
    {
        try {
            $areaCode = $this->appState->getAreaCode();
            if ($areaCode === Area::AREA_ADMINHTML) {
                return false;
            }
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
        }

        return true;
    }

    /**
     * @param $order
     * @param array $response
     *
     * @return void
     * @throws Exception
     */
    private function saveNgeniusOrder($order, array $response): void
    {
        $data = [
            'reference' => $response['orderReference'],
            'action'    => $response['transactionType'],
            'amount'    => $order->getGrandTotal(),
            'state'     => $this->orderStatusService->getDefaultPBLState(),
            'status'    => $this->orderStatusService->getDefaultStatus(),
            'order_id'  => $order->getIncrementId(),
            'entity_id' => $order->getEntityId(),
            'currency'  => $order->getOrderCurrencyCode(),
        ];

        $model = $this->ngeniusCoreFactory->create();
        $model->addData($data);
        $model->save();
    }
}
