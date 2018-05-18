<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Config\Backend\CustomSnippetUpload;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Exception\LocalizedException;

class Upload extends Action
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJson;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;

    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * @var CustomSnippetUpload
     */
    private $customSnippetUpload;

    /**
     * @var DateTime
     */
    private $time;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     *
     * Upload constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param CustomSnippetUpload $customSnippetUpload
     * @param DateTime $time
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        CustomSnippetUpload $customSnippetUpload,
        DateTime $time,
        TimezoneInterface $timezone
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->customSnippetUpload = $customSnippetUpload;
        $this->time = $time;
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * Upload VCL snippets
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $snippets = $this->config->getVclSnippets();
            $customSnippetPath = $this->customSnippetUpload->getUploadDirPath('vcl_snippets_custom');
            $customSnippets = $this->config->getCustomSnippets($customSnippetPath);

            foreach ($snippets as $key => $value) {
                $snippetData = [
                    'name'      => Config::FASTLY_MAGENTO_MODULE . '_' . $key,
                    'type'      => $key,
                    'dynamic'   => "0",
                    'priority'  => 50,
                    'content'   => $value
                ];
                $this->api->uploadSnippet($clone->number, $snippetData);
            }

            foreach ($customSnippets as $key => $value) {
                $snippetNameData = $this->validateCustomSnippet($key);
                $snippetType = $snippetNameData[0];
                $snippetPriority = $snippetNameData[1];
                $snippetShortName = $snippetNameData[2];

                $customSnippetData = [
                    'name'      => Config::FASTLY_MAGENTO_MODULE . '_' . $snippetShortName,
                    'type'      => $snippetType,
                    'priority'  => $snippetPriority,
                    'content'   => $value,
                    'dynamic'   => '0'
                ];
                $this->api->uploadSnippet($clone->number, $customSnippetData);
            }

            $condition = [
                'name'      => Config::FASTLY_MAGENTO_MODULE.'_pass',
                'statement' => 'req.http.x-pass',
                'type'      => 'REQUEST',
                'priority'  => 90
            ];
            $createCondition = $this->api->createCondition($clone->number, $condition);
            $request = [
                'action'            => 'pass',
                'max_stale_age'     => 3600,
                'name'              => Config::FASTLY_MAGENTO_MODULE.'_request',
                'request_condition' => $createCondition->name,
                'service_id'        => $service->id,
                'version'           => $currActiveVersion
            ];

            $this->api->createRequest($clone->number, $request);
            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*Upload VCL has been initiated and activated in version ' . $clone->number . '*'
                );
            }

            return $result->setData([
                'status'            => true,
                'active_version'    => $clone->number
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate custom snippet naming convention
     * [vcl_snippet_type]_[priority]_[short_name_description].vcl
     *
     * @param $customSnippet
     * @return array
     * @throws LocalizedException
     */
    private function validateCustomSnippet($customSnippet)
    {
        $snippetName = str_replace(' ', '', $customSnippet);
        $snippetNameData = explode('_', $snippetName, 3);
        $containsEmpty = in_array("", $snippetNameData, true);
        $types = ['init', 'recv', 'hit', 'miss', 'pass', 'fetch', 'error', 'deliver', 'none'];
        $exception = 'Failed to upload VCL snippets. Please make sure the custom VCL snippets 
            follow this naming convention: [vcl_snippet_type]_[priority]_[short_name_description].vcl';

        if (count($snippetNameData) < 3) {
            throw new LocalizedException(__($exception));
        }

        $inArray = in_array($snippetNameData[0], $types);
        $isNumeric = is_numeric($snippetNameData[1]);
        $isAlphanumeric = preg_match('/^[\w]+$/', $snippetNameData[2]);

        if ($containsEmpty || !$inArray || !$isNumeric || !$isAlphanumeric) {
            throw new LocalizedException(__($exception));
        }
        return $snippetNameData;
    }
}
