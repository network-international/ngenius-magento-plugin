<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace NetworkInternational\NGenius\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactory implements TransferFactoryInterface
{

    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        TransferBuilder $transferBuilder
    ) {
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {

        if ($request['token'] && is_array($request['request'])) {
            return $this->transferBuilder
                            ->setBody($request['request']['data'])
                            ->setMethod($request['request']['method'])
                            ->setHeaders([
                                'Authorization' => 'Bearer ' . $request['token'],
                                'Content-Type' => 'application/vnd.ni-payment.v2+json',
                                'Accept' => 'application/vnd.ni-payment.v2+json'
                            ])
                            ->setUri($request['request']['uri'])
                            ->build();
        }
    }

    public function tokenBuild(array $request, $apiKey)
    {
        if (is_array($request['request']) && isset($apiKey)) {
            return $this->transferBuilder
                            ->setBody($request['request']['data'])
                            ->setMethod($request['request']['method'])
                            ->setHeaders([
                                'Authorization' => 'Basic ' . $apiKey,
                                'Content-Type' => \Zend_Http_Client::ENC_URLENCODED
                            ])
                            ->setUri($request['request']['uri'])
                            ->build();
        }
    }
}
