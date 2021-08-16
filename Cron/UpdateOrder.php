<?php

namespace NetworkInternational\NGenius\Cron;

/**
 * Class UpdateOrder
 */
class UpdateOrder extends \NetworkInternational\NGenius\Controller\NGeniusOnline\Payment
{

    /**
     * Default execute function.
     *
     * @return null
     */
    public function execute()
    {
        try {
            $this->cronTask();
            $this->logger->info('Cron Works');
        } catch (\Exception $ex) {
            $this->logger->error($e->getMessage());
        }
    }
}
