<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

/*
 * Class TransactionSale
 */

class TransactionSale extends AbstractTransaction
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

        $response = json_decode($responseEnc);
        if (isset($response->_links->payment->href)) {
            $data = $this->checkoutSession->getTableData();

            $data['reference'] = isset($response->reference) ? $response->reference : '';
            $data['action'] = isset($response->action) ? $response->action : '';
            $data['state'] = isset($response->_embedded->payment[0]->state) ? $response->_embedded->payment[0]->state : '';
            $data['status'] = $this->orderStatus[0]['status'];
            $model = $this->coreFactory->create();
            $model->addData($data);
            $model->save();

            $this->checkoutSession->setPaymentURL($response->_links->payment->href);
            return ['payment_url' => $response->_links->payment->href];
        } else {
            return null;
        }
    }
}
