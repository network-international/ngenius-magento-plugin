<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

/*
 * Class TransactionRefund
 */

use Magento\Framework\Exception\LocalizedException;
use NetworkInternational\NGenius\Setup\Patch\Data\DataPatch;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\Processor\TransactionProcessor;

class TransactionRefund extends PaymentTransaction
{
    /**
     * Processing of API response
     *
     * @param string $responseEnc
     *
     * @return array|null
     * @throws LocalizedException
     */
    protected function postProcess(string $responseEnc): ?array
    {
        $responseArray = json_decode($responseEnc, true);

        if (isset($responseArray['errors']) && is_array($responseArray['errors'])) {
            throw new LocalizedException(
                __(
                    'This invoice has not been refunded: '
                    . $responseArray['errors'][0]['message']
                )
            );
        } else {
            $transactionProcessor = new TransactionProcessor($responseArray);
            $collection = $this->coreFactory->create()
                                            ->getCollection()
                                            ->addFieldToFilter('reference', $responseArray['orderReference']);

            $orderItem = $collection->getFirstItem();

            $lastRefundTransaction = $transactionProcessor->getLastRefundTransaction();
            $transactionId = $transactionProcessor->getTransactionID($lastRefundTransaction);

            $currencyCode = $orderItem->getData('currency');

            $capturedAmount = ValueFormatter::intToFloatRepresentation(
                $currencyCode,
                $transactionProcessor->getTotalCaptured()
            );
            $totalRefunded = ValueFormatter::intToFloatRepresentation(
                $currencyCode,
                $transactionProcessor->getTotalRefunded()
            );
            $lastRefundedAmount = ValueFormatter::intToFloatRepresentation(
                $currencyCode,
                $transactionProcessor->getTransactionAmount($lastRefundTransaction)
            );

            $state = $responseArray['state'] ?? '';

            if ($state === 'REVERSED') {
                $capturedAmount = $orderItem->getData('captured_amt');
                $orderItem->addData(['REVERSED' => 'REVERSED']);
            }

            if ($capturedAmount == 0) {
                $orderStatus = $this->orderStatus[7]['status'];
                $orderItem->setCaptureAmt(0);
            } elseif ($capturedAmount === $totalRefunded) {
                $orderStatus = $this->orderStatus[7]['status'];
                $orderItem->setCapturedAmt(($capturedAmount - $totalRefunded));
            } else {
                $orderStatus = $this->orderStatus[8]['status'];
                $orderItem->setCapturedAmt(($capturedAmount - $totalRefunded));
            }

            $orderItem->setState(DataPatch::STATE);
            $orderItem->setStatus($orderStatus);
            $orderItem->save();

            return [
                'result' => [
                    'total_refunded' => $totalRefunded,
                    'refunded_amt'   => $lastRefundedAmount,
                    'state'          => $state,
                    'order_status'   => $orderStatus,
                    'payment_id'     => $transactionId
                ]
            ];
        }
    }
}
