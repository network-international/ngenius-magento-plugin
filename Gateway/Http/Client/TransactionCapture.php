<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

/*
 * Class TransactionCapture
 */

use Magento\Framework\Exception\NoSuchEntityException;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\Processor\TransactionProcessor;

class TransactionCapture extends PaymentTransaction
{
    /**
     * Processing of API response
     *
     * @param string $responseEnc
     *
     * @return null|array
     * @throws NoSuchEntityException
     */
    protected function postProcess(string $responseEnc): ?array
    {
        $responseArray = json_decode($responseEnc, true);

        if (isset($responseArray['errors']) && is_array($responseArray['errors'])) {
            return null;
        } else {
            $transactionProcessor = new TransactionProcessor($responseArray);
            $totalCapturedAmount = $transactionProcessor->getTotalCaptured();
            $lastTransaction = $transactionProcessor->getLastCaptureTransaction();
            $transactionId = $transactionProcessor->getTransactionId($lastTransaction);

            $currencyCode = $lastTransaction['amount']['currencyCode'] ?? '';
            $totalCapturedAmount = ($totalCapturedAmount > 0) ? ValueFormatter::intToFloatRepresentation(
                $currencyCode,
                $totalCapturedAmount
            ) : 0;

            $capturedAmount     = 0;
            if (isset($lastTransaction['state'])
                && ($lastTransaction['state'] == 'SUCCESS')
                && isset($lastTransaction['amount']['value'])
            ) {
                $capturedAmount = ValueFormatter::intToFloatRepresentation($currencyCode, $transactionProcessor->getTransactionAmount($lastTransaction));

                ValueFormatter::formatCurrencyDecimals($currencyCode, $capturedAmount);
            }

            $collection = $this->coreFactory->create()->getCollection()->addFieldToFilter(
                'reference',
                $responseArray['orderReference']
            );
            $orderItem = $collection->getFirstItem();

            $storeId = $this->storeManager->getStore()->getId();

            if ($this->config->getCustomSuccessOrderStatus($storeId) != null) {
                $status = $this->config->getCustomSuccessOrderStatus($storeId);
            } else {
                $status = 'processing';
            }

            $state = $responseArray['state'];

            $orderItem->setState($state);
            $orderItem->setStatus($status);
            $orderItem->setCapturedAmt($capturedAmount);
            $orderItem->save();

            return [
                'result' => [
                    'total_captured' => $totalCapturedAmount,
                    'captured_amt'   => $capturedAmount,
                    'state'          => $state,
                    'order_status'   => $status,
                    'payment_id'     => $transactionId
                ]
            ];
        }
    }
}
