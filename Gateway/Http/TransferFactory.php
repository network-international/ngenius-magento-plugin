<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace NetworkInternational\NGenius\Gateway\Http;

use Magento\Payment\Gateway\Http\Transfer;
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
     *
     * @return TransferInterface|null
     */
    public function create(array $request): ?TransferInterface
    {
        if ($request['token'] && is_array($request['request'])) {
            return $this->transferBuilder
                ->setBody($request['request']['data'])
                ->setMethod($request['request']['method'])
                ->setHeaders(
                    [
                        'Authorization' => 'Bearer ' . $request['token'],
                        'Token'         => $request['token'],
                    ]
                )
                ->setUri($request['request']['uri'])
                ->build();
        }

        return null;
    }

    /**
     * Builds token request
     *
     * @param array $request
     * @param string $apiKey
     *
     * @return Transfer|TransferInterface|void
     */
    public function tokenBuild(array $request, string $apiKey)
    {
        if (is_array($request['request'])) {
            return $this->transferBuilder
                ->setBody($request['request']['data'])
                ->setMethod($request['request']['method'])
                ->setHeaders(
                    [
                        'Authorization' => 'Basic ' . $apiKey,
                        'Content-Type'  => 'application/vnd.ni-identity.v1+json',
                    ]
                )
                ->setUri($request['request']['uri'])
                ->build();
        }
    }
}
