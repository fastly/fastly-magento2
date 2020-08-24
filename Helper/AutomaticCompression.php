<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Helper;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class AutomaticCompression
 *
 * @package Fastly\Cdn\Helper
 */
class AutomaticCompression extends AbstractHelper
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param Api $api
     * @param Config $config
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Api $api,
        Config $config,
        Vcl $vcl
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->config = $config;
        $this->vcl = $vcl;
    }

    public function updateVclSnippet($value)
    {
        $service = $this->api->checkServiceDetails();
        $activeVersion = $this->vcl->getCurrentVersion($service->versions);

        $snippet = $this->api->getSnippet($activeVersion, Config::IMAGE_SETTING_NAME);
        if (!$snippet) {
            return;
        }
        $snippetData = $this->buildSnippetData($snippet, $value);

        $clone = $this->api->cloneVersion($activeVersion);
        $this->api->uploadSnippet($clone->number, $snippetData);
        $this->api->activateVersion($clone->number);
        $this->api->addComment($clone->number, ['comment' => 'Magento Module updated the Image Optimization snippet']);
    }

    protected function buildSnippetData($snippet, $value)
    {
        $defaultContent = $this->config->getVclSnippets(Config::IO_VCL_SNIPPET_PATH, 'recv.vcl');
        $defaultContent = array_shift($defaultContent);

        $pattern = '#set req.url = querystring.set(req.url, "optimize", "(low|medium|high)");';
        $replacement = implode('', [
            ($value === 'off') ? '#' : '',
            'set req.url = querystring.set(req.url, "optimize", "',
            $value,
            '");'
        ]);
        $content = str_replace($pattern, $replacement, $defaultContent);

        return [
            'name' => $snippet->name,
            'type' => $snippet->type,
            'dynamic' => $snippet->dynamic,
            'content' => $content,
            'priority' => $snippet->priority,
        ];
    }
}
