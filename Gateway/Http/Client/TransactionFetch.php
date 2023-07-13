<?php

namespace NetworkInternational\NGenius\Gateway\Http\Client;

class TransactionFetch extends PaymentTransaction
{
    /**
     * Processing of API response
     *
     * @param array $responseEnc
     * @return array|null
     */
    protected function postProcess($responseEnc): ?array
    {
        return json_decode($responseEnc, true);
    }
}
