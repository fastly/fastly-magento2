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
namespace Fastly\Cdn\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;

/**
 * Class BetterImageOptimization
 */
class BetterImageOptimization implements MessageInterface
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager;

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\FlagManager $flagManager
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->flagManager = $flagManager;
    }

    /**
     * @return string
     */
    public function getIdentity()
    {
        return 'fastly_cdn_betterimageoptimization';
    }

    /**
     * @return bool
     */
    public function isDisplayed()
    {
        $dismissedMessages = $this->flagManager->getFlagData('fastly_cdn_dismissed_messages') ?? [];
        return !in_array($this->getIdentity(), $dismissedMessages);
    }

    /**
     * @return string
     */
    public function getText()
    {
        $message = __(
            '<b>Fastly Better Image Optimization:</b> '
        );
        $message .= __(
            'New Automatic Compression attempts to produce an output image with as much visual quality as possible
while minimizing the file size. Click <a href="https://docs.fastly.com/en/image-optimization-api/optimize" target="_blank" rel="noopener noreferer">here</a>
for more info. '
        );
        $message .= __(
            '<a href="%1">Dismiss message</a>.',
            $this->urlBuilder->getUrl('adminhtml/system_message/dismiss', ['message_code' => $this->getIdentity()])
        );
        return $message;
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_NOTICE;
    }
}
