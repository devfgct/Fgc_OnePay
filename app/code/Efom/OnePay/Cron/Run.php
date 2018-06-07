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
		\Magento\Framework\Event\Manager $eventManager,
		\Psr\Log\LoggerInterface $logger
    ) {
		$this->_objectManager = $objectManager;
		$this->_orderCollectionFactory = $orderCollectionFactory;
		$this->_eventManager = $eventManager;
		$this->_logger = $logger;
    }

    public function execute() {
		$orderCollection = $this->_orderCollectionFactory->create()->addAttributeToSelect('*');
		$orderCollection->addFieldToFilter('status', 'onepay_exit');
		foreach ($orderCollection as $order) {
			$orderId = $order->getId();
			$created_at = $order->getCreatedAt();
			if(time() - strtotime($created_at) > 900) {
				$order->setStatus("payment_onepay_fail");
				$order->save();
				// event not working
				// $this->eventManager->dispatch('onepay_payment_status', ['status' => false, 'order' => $order]);
				$message = "OnePay: Set orderId {$orderId} to status 'onepay_payment_status'.";
			}
			$message = "OnePay: ignore orderId {$orderId} ({$created_at}).";
			$this->_logger->info($message);
			continue;
		}
        return $this;
    }
}
