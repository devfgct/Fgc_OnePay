<?php
namespace Efom\OnePay\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\TemplateTypesInterface;

/**
 * Class Recurring
 * @package Efom\OnePay\Setup
 */
class Recurring implements InstallSchemaInterface {
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * Recurring constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
		LoggerInterface $logger,
		\Efom\OnePay\Helper\Data $helper
    ) {
		$this->logger = $logger;
		$this->helper = $helper;
		$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $this->insertEmailTemplates();
	}

	protected function initTemplate($template_code='') {
        $model = $this->_objectManager->create(\Magento\Email\Model\BackendTemplate::class);
        if ($template_code) {
            $model->load($template_code, 'template_code');
        }
        return $model;
	}

	public function insertEmailTemplates() {
        $scopeConfig = $this->_objectManager->create(\Magento\Framework\App\Config\ScopeConfigInterface::class);
		$configWriter = $this->_objectManager->create(\Magento\Framework\App\Config\Storage\WriterInterface::class);
		if(!$this->helper->getConfigValue('payment/onepay_email_template/order_success')) {
			$data = [
				'template_subject' => '{{trans "Your %store_name order confirmation" store_name=$store.getFrontendName()}}',
				'template_code' => 'Order Success',
				'template_text' => $this->getBodyEmailOrderSuccess(),
				'template_styles' => '',
				'orig_template_code' => '',
				'orig_template_variables' => '',
				'_change_type_flag' => '',
			];
			$templateId = $this->insertEmailTemplate($data);

			if($templateId && $data['template_text']) {
				$configWriter->save('payment/onepay_email_template/order_success', $templateId, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
			}
		}
		if(!$this->helper->getConfigValue('payment/onepay_email_template/order_fail')) {
			$data = [
				'template_subject' => '{{trans "Your %store_name order confirmation" store_name=$store.getFrontendName()}}',
				'template_code' => 'Order Fail',
				'template_text' => $this->getBodyEmailOrderFail(),
				'template_styles' => '',
				'orig_template_code' => '',
				'orig_template_variables' => '',
				'_change_type_flag' => '',
			];
			$templateId = $this->insertEmailTemplate($data);
			if($templateId && $data['template_text']) {
				$configWriter->save('payment/onepay_email_template/order_fail', $templateId, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
			}
		}
	}
	
    protected function insertEmailTemplate($data=[]) {
        $template = $this->initTemplate($data['template_code']);
		try {
            $template->setTemplateSubject(
                $data['template_subject']
            )->setTemplateCode(
                $data['template_code']
            )->setTemplateText(
                $data['template_text']
            )->setTemplateStyles(
                $data['template_styles']
            )->setModifiedAt(
                $this->_objectManager->get(\Magento\Framework\Stdlib\DateTime\DateTime::class)->gmtDate()
            )->setOrigTemplateCode(
                $data['orig_template_code']
            )->setOrigTemplateVariables(
                $data['orig_template_variables']
            );

            if (!$template->getId()) {
                $template->setTemplateType(TemplateTypesInterface::TYPE_HTML);
            }

            if ($data['_change_type_flag']) {
                $template->setTemplateType(TemplateTypesInterface::TYPE_TEXT);
                $template->setTemplateStyles('');
            }

            $template->save();
            $templateId = $template->getId();
            return $templateId;
        } catch (\Exception $e) {}
        return false;
	}
	
    protected function getBodyEmailOrderSuccess() {
        ob_start(); ?>
{{template config_path="design/email/header_template"}}
<table>
    <tr class="email-intro">
        <td>
            <p class="greeting">{{trans "%customer_name," customer_name=$order.getCustomerName()}}</p>
            <p>
                {{trans "Thank you for your order from %store_name." store_name=$store.getFrontendName()}}
                {{trans "Once your package ships we will send you a tracking number."}}
                {{trans 'You can check the status of your order by <a href="%account_url">logging into your account</a>.' account_url=$this.getUrl($store,'customer/account/',[_nosid:1]) |raw}}
            </p>
            <p>
                {{trans 'If you have questions about your order, you can email us at <a href="mailto:%store_email">%store_email</a>' store_email=$store_email |raw}}{{depend store_phone}} {{trans 'or call us at <a href="tel:%store_phone">%store_phone</a>' store_phone=$store_phone |raw}}{{/depend}}.
                {{depend store_hours}}
                    {{trans 'Our hours are <span class="no-link">%store_hours</span>.' store_hours=$store_hours |raw}}
                {{/depend}}
            </p>
        </td>
    </tr>
    <tr class="email-summary">
        <td>
            <h1>{{trans 'Your Order <span class="no-link">#%increment_id</span>' increment_id=$order.increment_id |raw}}</h1>
            <p>{{trans 'Placed on <span class="no-link">%created_at</span>' created_at=$order.getCreatedAtFormatted(2) |raw}}</p>
        </td>
    </tr>
    <tr class="email-information">
        <td>
            {{depend order.getEmailCustomerNote()}}
            <table class="message-info">
                <tr>
                    <td>
                        {{var order.getEmailCustomerNote()|escape|nl2br}}
                    </td>
                </tr>
            </table>
            {{/depend}}
            <table class="order-details">
                <tr>
                    <td class="address-details">
                        <h3>{{trans "Billing Info"}}</h3>
                        <p>{{var formattedBillingAddress|raw}}</p>
                    </td>
                    {{depend order.getIsNotVirtual()}}
                    <td class="address-details">
                        <h3>{{trans "Shipping Info"}}</h3>
                        <p>{{var formattedShippingAddress|raw}}</p>
                    </td>
                    {{/depend}}
                </tr>
                <tr>
                    <td class="method-info">
                        <h3>{{trans "Payment Method"}}</h3>
                        {{var payment_html|raw}}
                    </td>
                    {{depend order.getIsNotVirtual()}}
                    <td class="method-info">
                        <h3>{{trans "Shipping Method"}}</h3>
                        <p>{{var order.getShippingDescription()}}</p>
                        {{if shipping_msg}}
                        <p>{{var shipping_msg}}</p>
                        {{/if}}
                    </td>
                    {{/depend}}
                </tr>
            </table>
            {{layout handle="sales_email_order_items" order=$order area="frontend"}}
        </td>
    </tr>
</table>

{{template config_path="design/email/footer_template"}}

		<?php
		$content = ob_get_clean();
        return $content;
	}
	
	protected function getBodyEmailOrderFail() {
        ob_start(); ?>
{{template config_path="design/email/header_template"}}
<table>
    <tr class="email-intro">
        <td>
            <p class="greeting">{{trans "%customer_name," customer_name=$order.getCustomerName()}}</p>
            <p>
                {{trans "Thank you for your order from %store_name." store_name=$store.getFrontendName()}}
                {{trans "Once your package ships we will send you a tracking number."}}
                {{trans 'You can check the status of your order by <a href="%account_url">logging into your account</a>.' account_url=$this.getUrl($store,'customer/account/',[_nosid:1]) |raw}}
            </p>
            <p>
                {{trans 'If you have questions about your order, you can email us at <a href="mailto:%store_email">%store_email</a>' store_email=$store_email |raw}}{{depend store_phone}} {{trans 'or call us at <a href="tel:%store_phone">%store_phone</a>' store_phone=$store_phone |raw}}{{/depend}}.
                {{depend store_hours}}
                    {{trans 'Our hours are <span class="no-link">%store_hours</span>.' store_hours=$store_hours |raw}}
                {{/depend}}
            </p>
        </td>
    </tr>
    <tr class="email-summary">
        <td>
            <h1>{{trans 'Your Order <span class="no-link">#%increment_id</span>' increment_id=$order.increment_id |raw}}</h1>
            <p>{{trans 'Placed on <span class="no-link">%created_at</span>' created_at=$order.getCreatedAtFormatted(2) |raw}}</p>
        </td>
    </tr>
    <tr class="email-information">
        <td>
            {{depend order.getEmailCustomerNote()}}
            <table class="message-info">
                <tr>
                    <td>
                        {{var order.getEmailCustomerNote()|escape|nl2br}}
                    </td>
                </tr>
            </table>
            {{/depend}}
            <table class="order-details">
                <tr>
                    <td class="address-details">
                        <h3>{{trans "Billing Info"}}</h3>
                        <p>{{var formattedBillingAddress|raw}}</p>
                    </td>
                    {{depend order.getIsNotVirtual()}}
                    <td class="address-details">
                        <h3>{{trans "Shipping Info"}}</h3>
                        <p>{{var formattedShippingAddress|raw}}</p>
                    </td>
                    {{/depend}}
                </tr>
                <tr>
                    <td class="method-info">
                        <h3>{{trans "Payment Method"}}</h3>
                        {{var payment_html|raw}}
                    </td>
                    {{depend order.getIsNotVirtual()}}
                    <td class="method-info">
                        <h3>{{trans "Shipping Method"}}</h3>
                        <p>{{var order.getShippingDescription()}}</p>
                        {{if shipping_msg}}
                        <p>{{var shipping_msg}}</p>
                        {{/if}}
                    </td>
                    {{/depend}}
                </tr>
            </table>
            {{layout handle="sales_email_order_items" order=$order area="frontend"}}
        </td>
    </tr>
</table>

{{template config_path="design/email/footer_template"}}
		<?php
		$content = ob_get_clean();
        return $content;
    }

}
