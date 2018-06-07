<?php
namespace Efom\OnePay\Cron;

class Run {
    protected $_helper;
    protected $_helper_request;
    protected $_storeManager;
    protected $curlClient;

    public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Magento\Framework\Event\Manager $eventManager
    ) {
		$this->_objectManager = $objectManager;
		$this->_orderCollectionFactory = $orderCollectionFactory;
		$this->_eventManager = $eventManager;
    }

    public function execute() {
		$orderCollection = $this->_orderCollectionFactory->create()->addAttributeToSelect('*');
		$orderCollection->addFieldToFilter('status', 'onepay_exit');
		foreach ($orderCollection as $order) {
			$data = $order->getData();
			$created_at = $order->getCreatedAt();
			if(time() - strtotime($created_at) > 900) {
				$order->setStatus("payment_oenpay_fail");
				$order->save();
				$this->eventManager->dispatch('onepay_payment_status', ['status' => false, 'order' => $order]);
			}
			continue;
		}
        return $this;
    }
}
