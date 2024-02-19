<?php

namespace Trovaprezzi\TrustedProgram\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;

class SetTrustedOnOrderSuccessPageViewObserver implements ObserverInterface
{
    /**
     * @var LayoutInterface
     */
    protected LayoutInterface $_layout;

    /**
     * @param LayoutInterface $layout
     */
    public function __construct(
        LayoutInterface $layout,
    ) {
        $this->_layout = $layout;
    }

    /**
     * Add order information into block to render on checkout success pages
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer): void
    {
        $orderIds = $observer->getEvent()->getOrderIds();

        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        $block = $this->_layout->getBlock('trustedprogram');
        if ($block) {
            $block->setOrderIds($orderIds);
        }
    }
}
