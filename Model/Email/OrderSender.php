<?php

namespace NetworkInternational\NGenius\Model\Email;

use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use NetworkInternational\NGenius\Gateway\Config\Config;

/**
 * Class OrderSender
 */
class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{

    private Config $config;

    public function __construct(
        Template $templateContainer,
        OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        OrderResource $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        ManagerInterface $eventManager,
        Config $config
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $orderResource,
            $globalConfig,
            $eventManager
        );
        $this->orderResource = $orderResource;
        $this->globalConfig  = $globalConfig;
        $this->config        = $config;
    }
    /**
     * Sends order email to the customer.
     *
     * Email will be sent immediately in two cases:
     *
     * - if asynchronous email sending is disabled in global settings
     * - if $forceSyncMode parameter is set to TRUE
     *
     * Otherwise, email will be sent later during running of
     * corresponding cron job.
     *
     * @param Order $order
     * @param bool $forceSyncMode
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function send(Order $order, $forceSyncMode = false)
    {
        $paymentCode = $order->getPayment()->getMethodInstance()->getCode();

        $storeId = $order->getStoreId();
        $emailOnOrder = $this->config->getEmailSend($storeId);

        $sendOrder = false;

        if ($order->isPaymentReview()) {
            if (!$emailOnOrder) {
                $sendOrder = true;
            }
        } else {
            if ($emailOnOrder) {
                $sendOrder = true;
            }
        }

        if ($paymentCode == \NetworkInternational\NGenius\Gateway\Config\Config::CODE && $sendOrder) {
            return false;
        } else {
            $order->setSendEmail(true);

            if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
                if ($this->checkAndSend($order)) {
                    $order->setEmailSent(true);
                    $this->orderResource->saveAttribute($order, ['send_email', 'email_sent']);
                    return true;
                }
            } else {
                $order->setEmailSent(null);
                $this->orderResource->saveAttribute($order, 'email_sent');
            }

            $this->orderResource->saveAttribute($order, 'send_email');
            return false;
        }
    }
}
