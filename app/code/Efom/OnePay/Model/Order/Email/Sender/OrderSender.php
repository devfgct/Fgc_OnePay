<?php
namespace Efom\OnePay\Model\Order\Email\Sender;

class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender { // Sender {
    /* public function __construct(
        \Magento\Sales\Model\Order\Email\Container\Template $templateContainer,
        \Magento\Sales\Model\Order\Email\Container\OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager
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
    } */

    public function send(\Magento\Sales\Model\Order $order, $forceSyncMode = false) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $code = $method->getCode();

		$design = $objectManager->create('\Magento\Framework\View\DesignInterface');
		$area = $design ? $design->getArea() : 'frontend';
		if(in_array($area, ['frontend', 'webapi_rest']) && in_array($code, ['onepay', 'onepayinternational'])) {
			return true;
		}
		
		return parent::send($order, $forceSyncMode);
    }
}
