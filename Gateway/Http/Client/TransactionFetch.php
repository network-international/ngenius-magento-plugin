<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

class TransactionFetch extends PaymentTransaction
{
    /**
     * Processing of API response
     *
     * @param string $responseEnc
     *
     * @return array|null
     */
    protected function postProcess(string $responseEnc): ?array
    {
        return json_decode($responseEnc, true);
    }
}
