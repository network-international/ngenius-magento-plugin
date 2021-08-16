<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

/*
 * Class TransactionFetch
 */

class TransactionFetch extends AbstractTransaction
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
        return json_decode($responseEnc, true);
    }
}
