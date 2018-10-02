<?php
namespace Fastly\Cdn\Model\Config\Backend;

/**
 * @api
 * @since 100.0.2
 */
class BlocklistAllowlist implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('Blocklist')], ['value' => 1, 'label' => __('Allowlist')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('Blocklist'), 1 => __('Allowlist')];
    }
}
