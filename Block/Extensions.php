<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Block;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Extensions extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $_layoutFactory;
    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $_moduleReader;
    /**
     * @var DecoderInterface
     */
    protected $_jsonDecoder;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_filesystem;
    /**
     * @var \Mygento\Base\Helper\Module
     */
    protected $_moduleHelper;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Filesystem\Driver\File $filesystem,
        \Mygento\Base\Helper\Module $moduleHelper,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);

        $this->_moduleList    = $moduleList;
        $this->_layoutFactory = $layoutFactory;
        $this->_moduleReader  = $moduleReader;
        $this->_jsonDecoder   = $jsonDecoder;
        $this->_filesystem    = $filesystem;
        $this->_moduleHelper  = $moduleHelper;
        $this->_scopeConfig   = $context->getScopeConfig();
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $modules = $this->_moduleList->getNames();

        $dispatchResult = new \Magento\Framework\DataObject($modules);
        $modules = $dispatchResult->toArray();

        sort($modules);
        foreach ($modules as $moduleName) {
            if (strstr($moduleName, 'Mygento_') === false
                || $moduleName === 'Mygento_Base'
            ) {
                continue;
            }

            $html .= $this->_getFieldHtml($element, $moduleName);
        }

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $layout = $this->_layoutFactory->create();

            $this->_fieldRenderer = $layout->createBlock(
                'Magento\Config\Block\System\Config\Form\Field'
            );
        }

        return $this->_fieldRenderer;
    }

    /**
     * Read info about extension from composer json file
     * @param $moduleCode
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function _getModuleInfo($moduleCode)
    {
        $dir = $this->_moduleReader->getModuleDir('', $moduleCode);
        $file = $dir . '/composer.json';

        $string = $this->_filesystem->fileGetContents($file);
        $json = $this->_jsonDecoder->decode($string);

        return $json;
    }

    /**
     * @param $fieldset
     * @param $moduleCode
     * @return string
     */
    protected function _getFieldHtml($fieldset, $moduleCode)
    {
        $module = $this->_getModuleInfo($moduleCode);
        if(!is_array($module)  ||
           !array_key_exists('version', $module) ||
           !array_key_exists('description', $module)
        ) {
            return '';
        }

        $currentVer = $module['version'];
        $moduleName = $module['description'];
        $status = 'status';

        // in case if module output disabled
        if ($this->_scopeConfig->getValue('advanced/modules_disable_output/' . $moduleCode)) {
            $status = __("Output disabled");
        }

        $moduleName = $status . ' ' . $moduleName;

        $field = $fieldset->addField($moduleCode, 'label', array(
            'name'  => 'dummy',
            'label' => $moduleName,
            'value' => $currentVer,
        ))->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }
}
