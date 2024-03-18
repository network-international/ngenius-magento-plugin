<?php

namespace NetworkInternational\NGenius\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;

/**
 * Validate and processes voided order
 *
 * Class VoidValidator
 */
class VoidValidator extends AbstractValidator
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
     * VoidValidator constructor.
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
     * @return ResultInterface|null
     */
    public function validate(array $validationSubject)
    {
        try {
            if (!empty($validationSubject)) {
                $response     = SubjectReader::readResponse($validationSubject);
                $paymentDO    = SubjectReader::readPayment($validationSubject);
                $orderAdapter = $paymentDO->getOrder();

                $order = $this->orderFactory->create()->load($orderAdapter->getId());

                if (!isset($response['result']) && !is_array($response['result'])) {
                    return $this->createResult(
                        false,
                        [__('Invalid void transaction.')]
                    );
                } else {
                    $order->addStatusToHistory(
                        $response['result']['order_status'],
                        'The authorization has been reversed successfully.',
                        false
                    );
                    $order->save();

                    return $this->createResult(true, []);
                }
            }
        } catch (\Exception $ex) {
            return $this->createResult(
                false,
                [__('Missing response data.')]
            );
        }

        return null;
    }
}
