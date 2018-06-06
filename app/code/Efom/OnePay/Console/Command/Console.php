<?php
namespace Efom\OnePay\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\{ObjectManager, State};

class Console extends Command {
	protected $_objectManager;
	protected $input;
	protected $output;
	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager
	) {
		$this->_objectManager = $objectManager;
		parent::__construct();
	}
	protected function configure() {
		$this->setName('onepay:test')->setDescription('');
		$this->addArgument('action', InputArgument::OPTIONAL, 'Type action', 'test');
		$this->addOption('sku', 'sku', InputOption::VALUE_REQUIRED, 'Product SKU', '01');
		$this->addOption('limit', 'limit', InputOption::VALUE_REQUIRED, 'Product SKU', null);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output) { // <info> <comment> <question> <error>
		$this->setAreaCode();
		$this->input = $input;
		$this->output = $output;
		$action = $method = $input->getArgument('action'); // getArgument | getOption
		
		$output->writeln('<info>Action: '.$action.'</info>');$output->writeln('');
		
		if(method_exists($this, $method)) {
			$output->writeln('<info>Calling method: $this->'.$method.'()</info>');$output->writeln('');
			$this->$method();
		} else {
			$output->writeln("<error>Unknown method '{$method}'</error>");
		}
	}
	
	protected function getPathModule() {
		$componentRegistrar = $this->_objectManager->create('\Magento\Framework\Component\ComponentRegistrarInterface');
		$path = $componentRegistrar->getPath(\Magento\Framework\Component\ComponentRegistrar::MODULE, $moduleName = 'Efom_OnePay');
		
		$this->output->writeln($path);
	}

	protected function importEmailTemplate() {
		//$scopeConfig = $this->_objectManager->create(\Magento\Framework\App\Config\ScopeConfigInterface::class);
		//echo $scopeConfig->getValue('payment/onepay_email_template/order_success', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		//$configWriter = $this->_objectManager->create(\Magento\Framework\App\Config\Storage\WriterInterface::class);
		//$configWriter->save('payment/onepay_email_template/order_success', $templateId, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
		$recurring = $this->_objectManager->create(\Efom\OnePay\Setup\Recurring::class);
		$recurring->insertEmailTemplates();

	}

	private function test() {
		$recurring = $this->_objectManager->create(\Efom\OnePay\Setup\Recurring::class);
		$recurring->insertEmailTemplates();
	}
	private function setAreaCode() {
        $areaCode = 'adminhtml';
        /** @var \Magento\Framework\App\State $appState */
        $appState = $this->_objectManager->get('Magento\Framework\App\State');
        $appState->setAreaCode($areaCode);
        /** @var \Magento\Framework\ObjectManager\ConfigLoaderInterface $configLoader */
        $configLoader = $this->_objectManager->get('Magento\Framework\ObjectManager\ConfigLoaderInterface');
        $this->_objectManager->configure($configLoader->load($areaCode));
    }
}
?>