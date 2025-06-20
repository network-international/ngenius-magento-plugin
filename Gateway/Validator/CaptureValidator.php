<?php

namespace NetworkInternational\NGenius\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;

/**
 * Validate and processes order capture
 *
 * Class CaptureValidator
 */
class CaptureValidator extends AbstractValidator
{
    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * CaptureValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param BuilderInterface $transactionBuilder
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        BuilderInterface $transactionBuilder,
        OrderFactory $orderFactory
    ) {
        $this->transactionBuilder = $transactionBuilder;
        $this->orderFactory       = $orderFactory;
        parent::__construct($resultFactory);
    }

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response     = SubjectReader::readResponse($validationSubject);
        $paymentDO    = SubjectReader::readPayment($validationSubject);
        $payment      = $paymentDO->getPayment();
        $orderAdapter = $paymentDO->getOrder();

        $order = $this->orderFactory->create()->load($orderAdapter->getId());

        if (!isset($response['result']) && !is_array($response['result'])) {
            return $this->createResult(
                false,
                [__('Invalid capture transaction.')]
            );
        } else {
            $paymentData = [
                'Captured Amount' =>
                    $order->getBaseCurrency()->formatTxt($response['result']['captured_amt'])
            ];
            $payment->setTransactionId($response['result']['payment_id']);
            $transaction = $this->transactionBuilder->setPayment($payment)
                                                    ->setOrder($order)
                                                    ->setTransactionId($response['result']['payment_id'])
                                                    ->setAdditionalInformation(
                                                        [Transaction::RAW_DETAILS => (array)$paymentData]
                                                    )
                                                    ->setFailSafe(true)
                                                    ->build(
                                                        Transaction::TYPE_CAPTURE
                                                    );
            $payment->addTransactionCommentsToOrder($transaction, null);
            $payment->save();
            $order->addStatusToHistory(
                $response['result']['order_status'],
                'The capture has been processed successfully.',
                false
            );
            $order->save();

            return $this->createResult(true, []);
        }
    }
}
