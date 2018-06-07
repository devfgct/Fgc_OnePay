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
		\Magento\Framework\App\Filesystem\DirectoryList $dir,
		\Psr\Log\LoggerInterface $logger
    ) {
		$this->_objectManager = $objectManager;
		$this->_orderCollectionFactory = $orderCollectionFactory;
		$this->_eventManager = $eventManager;
		$this->_dir = $dir;
		$this->_logger = $logger;
		$this->_logger->pushHandler(new \Monolog\Handler\StreamHandler($this->_dir->getRoot().'/var/log/onepay.log'));
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
				$this->_eventManager->dispatch('onepay_payment_status', ['status' => false, 'order' => $order]);
				$message = "OnePay: Set orderId {$orderId} to status 'onepay_payment_status'.";
			} else {
				$message = "OnePay: ignore orderId {$orderId} ({$created_at}).";
			}
			$this->_logger->info($message);
			continue;
		}
        return $this;
    }
}
