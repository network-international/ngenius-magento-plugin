<?php

namespace NetworkInternational\NGenius\Model\Config;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 */
class OutletCurrencies implements ArrayInterface
{
    public const ACTION_PURCHASE = 'purchased';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $currencies = [
            'AED',
            'AOA',
            'AUD',
            'BHD',
            'BWP',
            'CAD',
            'DKK',
            'EGP',
            'EUR',
            'GBP',
            'GHS',
            'GNF',
            'HKD',
            'INR',
            'JOD',
            'JPY',
            'KES',
            'KWD',
            'LKR',
            'MAD',
            'MWK',
            'MYR',
            'NAD',
            'NGN',
            'OMR',
            'PHP',
            'PKR',
            'QAR',
            'SAR',
            'SEK',
            'SGD',
            'THB',
            'TRY',
            'TZS',
            'UGX',
            'USD',
            'XAF',
            'XOF',
            'ZAR',
            'ZMW',
            'ZWL'
        ];

        $return = [];
        foreach ($currencies as $currency) {
            $return[] = [
                'value' => $currency,
                'label' => __($currency),
            ];
        }

        return $return;
    }
}
