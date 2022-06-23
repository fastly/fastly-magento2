<?php
declare(strict_types=1);

namespace Fastly\Cdn\Model\Snippet;

use Fastly\Cdn\Model\Config;

/**
 * Class to check can snippet be deleted, predefined snippets can't be deleted
 */
class CheckIsAllowedForSync
{

    /**
     * @var array
     */
    private $predefinedList;

    /**
     * CheckIsAllowedForSync constructor.
     *
     * @param array $predefinedList
     */
    public function __construct(array $predefinedList = [])
    {
        $this->predefinedList = $predefinedList;
    }

    /**
     * Check is snippet name allowed for sync / removing
     *
     * @param string $snippetName
     * @return bool
     */
    public function checkIsAllowed(string $snippetName): bool
    {
        if (strpos($snippetName, Config::FASTLY_MAGENTO_MODULE . '_') !== 0) {
            return false;
        }

        foreach ($this->predefinedList as $disableName) {
            if (strpos($snippetName, $disableName) === 0) {
                return false;
            }
        }
        return true;
    }
}
