<?php

namespace NetworkInternational\NGenius\Controller\Adminhtml\Core;

/**
 * Class Report
 *
 * Provides the client with the NGenius Report Resource
 */
class Report extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory = false;

    /**
     * Report constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Default execute function.
     *
     * @return PageFactory
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('N-Genius Report')));

        return $resultPage;
    }
}
