<?php

namespace NetworkInternational\NGenius\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Asset\Repository;
use Psr\Log\LoggerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    private $assetRepo;
    private $logger;

    public function __construct(
        Repository $assetRepo,
        LoggerInterface $logger
    ) {
        $this->assetRepo = $assetRepo;
        $this->logger    = $logger;
    }

    public function getConfig()
    {
        $logoUrl = $this->assetRepo->getUrl('NetworkInternational_NGenius::images/ngenius_logo.png');

        return [
            'payment' => [
                'ngeniusonline' => [
                    'logoSrc' => $logoUrl
                ]
            ]
        ];
    }
}
