<?php
namespace Efom\OnePay\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {
	protected $_filesystem;
	protected $_directory_list;
	protected $_resource;
	protected $_storeManager;
	protected $_scopeConfig;
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\Filesystem $filesystem,
		\Magento\Framework\App\ResourceConnection $resource,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Module\Dir\Reader $moduleReader
    ) {
		parent::__construct($context);
		$this->_filesystem = $filesystem;
        $this->_directory_list = $directory_list;
		$this->_resource = $resource;
		$this->_storeManager = $storeManager;
		$this->_scopeConfig = $scopeConfig;
		$this->_moduleReader = $moduleReader;
	}

	public function getConfigValue($xmlPath) {
        return $this->_scopeConfig->getValue($xmlPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getEmailTemplateContent($templateName) {
		$viewDir = $this->_moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
            'Efom_OnePay'
        );
		$path = $viewDir . '/frontend/email/'.$templateName;
		if(file_exists($path)) return file_get_contents($path);
		return '';
	}
}
