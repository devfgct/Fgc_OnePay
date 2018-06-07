<?php
namespace Efom\OnePay\Observer;
use Magento\Framework\Event\ObserverInterface;

class Sendmail implements ObserverInterface {

	const XML_PATH_EMAIL_TEMPLATE = 'sales_email/order/template';
	const XML_PATH_EMAIL_GUEST_TEMPLATE = 'sales_email/order/guest_template';
	
	protected $_coreRegistry;
	protected $_authSession;

	protected $_paymentList = ['onepay', 'onepayinternational'];
	protected $_paymentCode;

	public function __construct(
		\Magento\Framework\Registry $coreRegistry,
		\Magento\Backend\Model\Auth\Session $authSession,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Framework\App\RequestInterface $request,
		\Efom\OnePay\Helper\Data $helper,

		\Magento\Framework\App\Filesystem\DirectoryList $dir,
		\Psr\Log\LoggerInterface $logger
	) {
		$this->_coreRegistry = $coreRegistry;
		$this->_authSession = $authSession;
		$this->_customerSession = $customerSession;
		$this->_request = $request;
		$this->_helper = $helper;
        $this->_dir = $dir;
		$this->_logger = $logger;
		//$this->_logger->pushHandler(new \Monolog\Handler\StreamHandler($this->_dir->getRoot().'/var/log/onepay.log'));
        $this->_orderStatus = null;
	}
    public function execute(\Magento\Framework\Event\Observer $observer) {
		$event = $observer->getEvent();
		$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		//$this->templateContainer = $this->_objectManager->get('\Magento\Sales\Model\Order\Email\Container\Template');
		//$this->identityContainer = $this->_objectManager->get('\Magento\Sales\Model\Order\Email\Container\OrderIdentity');
		//$this->senderBuilderFactory = $this->_objectManager->get('\Magento\Sales\Model\Order\Email\SenderBuilderFactory');
		$this->addressRenderer = $this->_objectManager->get('\Magento\Sales\Model\Order\Address\Renderer');


		$order = $this->_order = $event->getOrder();
		$orderId = $this->_request->getParam('order_id');
		if ($order instanceof \Magento\Framework\Model\AbstractModel) {
			$origData = $order->getOrigData();
			$data = $order->getData();
            $state = $order->getState();
            $status = $order->getStatus();
			$payment = $order->getPayment();
			$method = $payment->getMethodInstance();
			$this->_paymentCode = $method->getCode();

			if($event->getName() == 'onepay_payment_status') {
				$this->_orderStatus = $event->getStatus();
                $this->checkAndSend($order);

                $message = "Send order email orderId ".$order->getId() .' (#'. $order->getIncrementId() .')';
                $this->_logger->info("[OnePay] {$message}");
			} elseif(in_array($this->_paymentCode, $this->_paymentList) && $status == 'payment_onepay_fail') {
                return;
                // Use if event not working in cronjob
                $this->_orderStatus = false;
                $this->checkAndSend($order);
            }
			return;
		}
		return $this;
	}

	/**
     * @param Order $order
     * @return bool
     */
    protected function checkAndSend(\Magento\Sales\Model\Order $order) {
        $this->prepareTemplate($order);
        $sender = $this->getSender();
        try {
            $sender->send();
            $sender->sendCopyTo();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->_logger->error("[OnePay] {$message}");
        }
        return true;
    }

    protected function prepareTemplate(\Magento\Sales\Model\Order $order) {
		$this->templateContainer = $this->_objectManager->get('\Magento\Sales\Model\Order\Email\Container\Template');
		$this->identityContainer = $this->_objectManager->get('\Magento\Sales\Model\Order\Email\Container\OrderIdentity');

		$transport = [
            'order' => $order,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
        ];
        $transport = new \Magento\Framework\DataObject($transport);

		$this->templateContainer->setTemplateVars($transport->getData());

		$this->templateContainer->setTemplateOptions($this->getTemplateOptions());
		
		//if(in_array($this->_paymentCode, $this->_paymentList)) {}
		
        if ($order->getCustomerIsGuest()) {
			// $templateId = $this->getGuestTemplateId();
			$templateId = $this->getTemplateId();
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $templateId = $this->getTemplateId();
            $customerName = $order->getCustomerName();
        }

        $this->identityContainer->setCustomerName($customerName);
        $this->identityContainer->setCustomerEmail($order->getCustomerEmail());
        $this->templateContainer->setTemplateId($templateId);
	}

    public function getGuestTemplateId() {
        return $this->_helper->getConfigValue(self::XML_PATH_EMAIL_GUEST_TEMPLATE);
    }

    /**
     * Return template id
     *
     * @return mixed
     */
    public function getTemplateId() {
		if($this->_orderStatus) $xmlPath = 'payment/onepay_email_template/order_success';
		else  $xmlPath = 'payment/onepay_email_template/order_fail';
		return $this->_helper->getConfigValue($xmlPath);
    }
	
	/**
     * Get payment info block as html
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
	protected function getPaymentHtml(\Magento\Sales\Model\Order $order) {
		$paymentHelper = $this->_objectManager->get('\Magento\Payment\Helper\Data');
        return $paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $this->identityContainer->getStore()->getStoreId()
        );
	}
	
    /**
     * @return Sender
     */
    protected function getSender() {
		$this->senderBuilderFactory = $this->_objectManager->get('\Magento\Sales\Model\Order\Email\SenderBuilderFactory');
        return $this->senderBuilderFactory->create(
            [
                'templateContainer' => $this->templateContainer,
                'identityContainer' => $this->identityContainer,
            ]
        );
    }

    /**
     * @return array
     */
    protected function getTemplateOptions() {
        return [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $this->identityContainer->getStore()->getStoreId()
        ];
    }

    /**
     * @param Order $order
     * @return string|null
     */
    protected function getFormattedShippingAddress($order) {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * @param Order $order
     * @return string|null
     */
    protected function getFormattedBillingAddress($order) {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }
}
