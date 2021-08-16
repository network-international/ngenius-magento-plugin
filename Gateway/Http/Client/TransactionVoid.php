<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

/*
 * Class TransactionVoid
 */

class TransactionVoid extends AbstractTransaction
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
            $collection = $this->coreFactory->create()->getCollection()->addFieldToFilter('reference', $response['orderReference']);
            $orderItem = $collection->getFirstItem();

            $state = isset($response['state']) ? $response['state'] : '';
            $order_status = ($state == 'REVERSED') ? $this->orderStatus[9]['status'] : '';

            $orderItem->setState($state);
            $orderItem->setStatus($order_status);
            $orderItem->save();
            return [
                'result' => [
                    'state' => $state,
                    'order_status' => $order_status
                ]
            ];
        }
    }
}
