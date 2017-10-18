<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Base
 */

namespace Mygento\Base\Block;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    protected $_store;

    /**
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     * @param \Magento\Framework\Filesystem\Driver\File $filesystem
     * @param \Mygento\Base\Helper\Module $moduleHelper
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Filesystem\Driver\File $filesystem,
        \Mygento\Base\Helper\Module $moduleHelper,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);

        $this->_moduleList = $moduleList;
        $this->_layoutFactory = $layoutFactory;
        $this->_moduleReader = $moduleReader;
        $this->_jsonDecoder = $jsonDecoder;
        $this->_filesystem = $filesystem;
        $this->_moduleHelper = $moduleHelper;
        $this->_store = $store;
        $this->_scopeConfig = $context->getScopeConfig();
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

        $site = 'https://www.mygento.net';
        $email = 'connect@mygento.net';

        if ($this->_store->getLocaleCode() == 'ru_RU') {
            $site = 'https://www.mygento.ru';
            $email = 'connect@mygento.ru';
        }

        $ticketUrl = "mailto:support@mygento.ru";
        $url = __(
            'Purchased extensions support is available through '
            . '<a href="%1" target="_blank">ticket tracking system</a>',
            $ticketUrl
        );
        $bugs = __('Please report all bugs and feature requests.');
        $emailtext = __(
            'If for some reasons you can not submit ticket '
            . 'to our system, you can write us an email %1.',
            $email
        );
        $hiretext = __(
            'You can hire us for any Magento extension customization and development.'
            . '<br/>Write us to %1',
            $email
        );
        $tender = __('Tender offer can be checked '
            . '<a href="https://www.mygento.ru/oferta" target="_blank">here</a>');

        $html .= '<table class="mygento-info" cellspacing="0" cellpading="0">'
            . '<tr class="mygento-info-line">';
        $html .= '<tr><td>' . __('Support') . ':</td>' .
            '<td>' . $url . '.<br/><br/>' . $bugs .
            '<br/><br/>' . $emailtext . '</td></tr>';
        $html .= '<tr><td>' . __('License') . ':</td><td>' . $tender . '</td></tr>';
        $html .= '<tr class="mygento-info-line "><td>'
            . '<img src="//www.mygento.ru/media/wysiwyg/logo_base.png" width="100" height="100"/>'
            . '</td><td>' . $hiretext . '<br/><br/>' . __(
                'You can check all providable services on '
                . '<a href="%1" target="_blank">our website</a>.',
                $site . '/services'
            ) . '</td></tr><tr class="mygento-info-line"></tr>';
        $html .= '</table>';

        $modules = $this->_moduleList->getNames();

        $dispatchResult = new \Magento\Framework\DataObject($modules);
        $modules = $dispatchResult->toArray();

        $html .= '<h2>' . __('Installed Extensions') . '</h2>';
        $html .= '<ul class="mygento-mod-list">';
        sort($modules);
        foreach ($modules as $moduleName) {
            if (strstr($moduleName, 'Mygento_') === false
                || $moduleName === 'Mygento_Base'
            ) {
                continue;
            }

            $html .= $this->_getFieldHtml($element, $moduleName);
        }
        $html .= '</ul>';

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
                \Magento\Config\Block\System\Config\Form\Field::class
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
        $file = $dir . DIRECTORY_SEPARATOR . 'composer.json';

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
        if (!is_array($module) ||
            !array_key_exists('version', $module) ||
            !array_key_exists('description', $module)
        ) {
            return '';
        }

        $currentVer = $module['version'];
        $moduleName = $module['description'];
        $status = '<span class="mygento-icon-success"></span>';

        // in case if module output disabled
        if ($this->_scopeConfig->getValue('advanced/modules_disable_output/' . $moduleCode)) {
            $status = __('Output disabled');
        }

        $field = $fieldset->addField($moduleCode, 'label', [
            'name' => 'dummy',
            'label' => $moduleName,
            'value' => $currentVer,
        ])->setRenderer($this->_getFieldRenderer());

        return '<li>' . $status . $field->toHtml() . '</li>';
    }
}
