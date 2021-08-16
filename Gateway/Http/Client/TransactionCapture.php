<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

/*
 * Class TransactionCapture
 */

class TransactionCapture extends AbstractTransaction
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
     * @return null|array
     */
    protected function postProcess($responseEnc)
    {

        $response = json_decode($responseEnc, true);

        if (isset($response['errors']) && is_array($response['errors'])) {
            return null;
        } else {
            $amount = 0;
            if (isset($response['_embedded']['cnp:capture']) && is_array($response['_embedded']['cnp:capture'])) {
                $lastTransaction = end($response['_embedded']['cnp:capture']);
                foreach ($response['_embedded']['cnp:capture'] as $capture) {
                    if (isset($capture['state']) && ($capture['state'] == 'SUCCESS') && isset($capture['amount']['value'])) {
                        $amount += $capture['amount']['value'];
                    }
                }
            }
            $captured_amt = 0;
            if (isset($lastTransaction['state']) && ($lastTransaction['state'] == 'SUCCESS') && isset($lastTransaction['amount']['value'])) {
                $captured_amt = $lastTransaction['amount']['value'] / 100;
            }

            $transactionId = '';
            if (isset($lastTransaction['_links']['self']['href'])) {
                $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                $transactionId = end($transactionArr);
            }
            $amount = ($amount > 0) ? $amount / 100 : 0;
            $collection = $this->coreFactory->create()->getCollection()->addFieldToFilter('reference', $response['orderReference']);
            $orderItem = $collection->getFirstItem();
            $state = isset($response['state']) ? $response['state'] : '';

            if ($state == 'PARTIALLY_CAPTURED') {
                $order_status = $this->orderStatus[6]['status'];
            } else {
                $order_status = $this->orderStatus[5]['status'];
            }
            $orderItem->setState($state);
            $orderItem->setStatus($order_status);
            $orderItem->setCapturedAmt($amount);
            $orderItem->save();
            return [
                'result' => [
                    'total_captured' => $amount,
                    'captured_amt' => $captured_amt,
                    'state' => $state,
                    'order_status' => $order_status,
                    'payment_id' => $transactionId
                ]
            ];
        }
    }
}
