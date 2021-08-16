<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

/*
 * Class TransactionRefund
 */

class TransactionRefund extends AbstractTransaction
{

    /**
     * Processing of API request body
     *
     * @param array $data
     * @return string
     */
    protected function preProcess(array $data)
    {
        return json_encode($data);
    }

    /**
     * Processing of API response
     *
     * @param array $responseEnc
     * @return array
     */
    protected function postProcess($responseEnc)
    {

        $response = json_decode($responseEnc, true);

        if (isset($response['errors']) && is_array($response['errors'])) {
            return null;
        } else {
            $captured_amt = 0;
            if (isset($response['_embedded']['cnp:capture']) && is_array($response['_embedded']['cnp:capture'])) {
                foreach ($response['_embedded']['cnp:capture'] as $capture) {
                    if (isset($capture['state']) && ($capture['state'] == 'SUCCESS') && isset($capture['amount']['value'])) {
                        $captured_amt += $capture['amount']['value'];
                    }
                }
            }

            $refunded_amt = 0;
            if (isset($response['_embedded']['cnp:refund']) && is_array($response['_embedded']['cnp:refund'])) {
                $lastTransaction = end($response['_embedded']['cnp:refund']);
                foreach ($response['_embedded']['cnp:refund'] as $refund) {
                    if (isset($refund['state']) && ($refund['state'] == 'SUCCESS') && isset($refund['amount']['value'])) {
                        $refunded_amt += $refund['amount']['value'];
                    }
                }
            }

            $last_refunded_amt = 0;
            if (isset($lastTransaction['state']) && ($lastTransaction['state'] == 'SUCCESS') && isset($lastTransaction['amount']['value'])) {
                $last_refunded_amt = $lastTransaction['amount']['value'] / 100;
            }

            $transactionId = '';
            if (isset($lastTransaction['_links']['self']['href'])) {
                $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                $transactionId = end($transactionArr);
            }

            $collection = $this->coreFactory->create()->getCollection()->addFieldToFilter('reference', $response['orderReference']);
            $orderItem = $collection->getFirstItem();
            $state = isset($response['state']) ? $response['state'] : '';

            if ($captured_amt == $refunded_amt) {
                $order_status = $this->orderStatus[7]['status'];
            } else {
                $order_status = $this->orderStatus[8]['status'];
            }
            $orderItem->setState($state);
            $orderItem->setStatus($order_status);
            $orderItem->setCapturedAmt(($captured_amt - $refunded_amt) / 100);
            $orderItem->save();
            return [
                'result' => [
                    'total_refunded' => $refunded_amt,
                    'refunded_amt' => $last_refunded_amt,
                    'state' => $state,
                    'order_status' => $order_status,
                    'payment_id' => $transactionId
                ]
            ];
        }
    }
}
