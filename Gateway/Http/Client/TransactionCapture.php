<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

/*
 * Class TransactionCapture
 */

use Magento\Framework\Exception\NoSuchEntityException;

class TransactionCapture extends PaymentTransaction
{
    /**
     * Processing of API response
     *
     * @param array $responseEnc
     *
     * @return null|array
     * @throws NoSuchEntityException
     */
    protected function postProcess($responseEnc): ?array
    {
        $response = json_decode($responseEnc, true);

        if (isset($response['errors']) && is_array($response['errors'])) {
            return null;
        } else {
            $transaction_data = $this->getTransactionData($response);
            $amount           = $transaction_data['amount'];
            $lastTransaction  = $transaction_data['last_transaction'];
            $captured_amt     = 0;
            if (isset($lastTransaction['state'])
                && ($lastTransaction['state'] == 'SUCCESS')
                && isset($lastTransaction['amount']['value'])
            ) {
                $captured_amt = $lastTransaction['amount']['value'] / 100;
            }

            $transactionId = $this->getTransactionId($lastTransaction);
            $amount        = ($amount > 0) ? $amount / 100 : 0;
            $collection    = $this->coreFactory->create()->getCollection()->addFieldToFilter(
                'reference',
                $response['orderReference']
            );
            $orderItem     = $collection->getFirstItem();

            $storeId = $this->storeManager->getStore()->getId();

            if ($this->config->getCustomSuccessOrderStatus($storeId) != null) {
                $status = $this->config->getCustomSuccessOrderStatus($storeId);
            } else {
                $status = 'processing';
            }

            if ($this->config->getCustomSuccessOrderState($storeId) != null) {
                $state = $this->config->getCustomSuccessOrderState($storeId);
            } else {
                $state = 'processing';
            }

            $orderItem->setState($state);
            $orderItem->setStatus($status);
            $orderItem->setCapturedAmt($amount);
            $orderItem->save();

            return [
                'result' => [
                    'total_captured' => $amount,
                    'captured_amt'   => $captured_amt,
                    'state'          => $state,
                    'order_status'   => $status,
                    'payment_id'     => $transactionId
                ]
            ];
        }
    }

    /**
     * Retrieves transaction link
     *
     * @param array $lastTransaction
     *
     * @return false|string
     */
    public function getTransactionId(array $lastTransaction): bool|string
    {
        if (isset($lastTransaction['_links']['self']['href'])) {
            $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);

            return end($transactionArr);
        }
        return false;
    }

    /**
     * Gets NGenius payment data
     *
     * @param array $response
     * @return array
     */
    public function getTransactionData(array $response): array
    {
        $embedded        = "_embedded";
        $cnpcapture      = "cnp:capture";
        $amount          = 0;
        $lastTransaction = "";
        if (isset($response[$embedded][$cnpcapture]) && is_array($response[$embedded][$cnpcapture])) {
            $lastTransaction = end($response[$embedded][$cnpcapture]);
            foreach ($response[$embedded][$cnpcapture] as $capture) {
                if (isset($capture['state'])
                    && ($capture['state'] == 'SUCCESS')
                    && isset($capture['amount']['value'])
                ) {
                    $amount += $capture['amount']['value'];
                }
            }
        }

        return [
            'amount'           => $amount,
            'last_transaction' => $lastTransaction
        ];
    }
}
