<?php

declare(strict_types=1);

namespace Trovaprezzi\TrustedProgram\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;

class Trusted extends Template
{
    /**
     * Config paths for using throughout the code
     */
    const XML_PATH_ACTIVE = 'tptp/trusted/active';

    const XML_PATH_ACCOUNT = 'tptp/trusted/account';

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $_salesOrderCollection;

    /**
     * @param Context $context
     * @param CollectionFactory $salesOrderCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $salesOrderCollection,
        array $data = []
    ) {
        $this->_salesOrderCollection = $salesOrderCollection;
        parent::__construct($context, $data);
    }

    /**
     * Get config
     *
     * @param string $path
     * @return mixed
     */
    public function getConfig(string $path): string
    {
        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE) ?: '';
    }

    /**
     * Render information about specified orders and their items
     *
     * @param string $accountId
     * @return string
     */
    public function getOrdersTrackingCode(string $accountId): string
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return '';
        }

        $collection = $this->_salesOrderCollection->create();
        $collection->addFieldToFilter('entity_id', ['in' => $orderIds]);
        $result = [];

        foreach ($collection as $order) {

			foreach ($order->getAllVisibleItems() as $item) {
				$prodRows[]= "window._tpt.push({ event: \"addItem\", sku: '".$this->escapeJsQuote($item->getSku())."', product_name: '".$this->escapeJsQuote($item->getName())."' });";
			}

			$prod= implode("\n", $prodRows);

            $result[] = sprintf(

                "function tpt_push() {
                    window._tpt.push({ event: \"setAccount\", id: '%s' });
				    window._tpt.push({ event: \"setOrderId\", order_id: '%s' });
				    window._tpt.push({ event: \"setEmail\", email: '%s' });
				    %s
				    window._tpt.push({ event: \"setAmount\", amount: '%s' });
                    window._tpt.push({ event: \"orderSubmit\"});
                };",
				$accountId,					//MKEY
                $order->getIncrementId(),	//ID
                $order->getCustomerEmail(),	//MAIL
				$prod,						//SKU-NAME
                $order->getBaseGrandTotal() - $order->getBaseShippingAmount() 	//AMOUNT
            );

            $result[] = "if (window._tpt === undefined) {
                            window.addEventListener('load', tpt_push);
                        } else {
                            tpt_push();
                        }";
        }
        return implode("\n", $result);
    }

    /**
     * Retrieves the tracking jQuery code.
     *
     * @return string
     */
	public function getTrackingJquery(): string
    {
	    $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return '';
        }
		return "<script type=\"text/javascript\" src=\"https://tracking.trovaprezzi.it/javascripts/tracking-vanilla.min.js\"></script>";
	}

    /**
     * Render GA tracking scripts
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->isTPAvailable()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Check if Trusted is enabled and with key
     *
     * @return bool
     */
    private function isTPAvailable(): bool
    {
        $accountId = $this->getConfig(self::XML_PATH_ACCOUNT);
        return $accountId && $this->_scopeConfig->isSetFlag(self::XML_PATH_ACTIVE);
    }
}
