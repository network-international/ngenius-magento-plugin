<?php

namespace NetworkInternational\NGenius\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Class ResponseValidator
 */
class ResponseValidator extends AbstractValidator
{
    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {

        $response = SubjectReader::readResponse($validationSubject);

        if (!isset($response['payment_url']) && filter_var($response['payment_url'], FILTER_VALIDATE_URL) === false) {
            return $this->createResult(
                false,
                [__('Invalid Payment Page URL.')]
            );
        } else {
            return $this->createResult(true, []);
        }
    }
}
