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
namespace Fastly\Cdn\Controller\Adminhtml\System\Message;

use Magento\Backend\App\Action;
use Magento\Framework\FlagManager;

/**
 * Class Dismiss
 */
class Dismiss extends Action
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    public function __construct(
        Action\Context $context,
        FlagManager $flagManager
    ) {
        parent::__construct($context);
        $this->flagManager = $flagManager;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $dismissedMessages = $this->flagManager->getFlagData('fastly_cdn_dismissed_messages') ?? [];
        array_push($dismissedMessages, $this->getRequest()->getParam('message_code'));
        $this->flagManager->saveFlag('fastly_cdn_dismissed_messages', array_unique($dismissedMessages));

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
