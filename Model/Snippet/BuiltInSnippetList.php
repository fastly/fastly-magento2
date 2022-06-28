<?php
declare(strict_types=1);

namespace Fastly\Cdn\Model\Snippet;

use Fastly\Cdn\Model\Config;

/**
 * Class to check is snippet builtin
 */
class BuiltInSnippetList
{

    /**
     * @var array
     */
    private $predefinedList;

    /**
     * BuiltInSnippetList constructor.
     *
     * @param array $predefinedList
     */
    public function __construct(array $predefinedList = [])
    {
        $this->predefinedList = $predefinedList;
    }

    /**
     * Check is builtin snippet
     *
     * @param string $snippetName
     * @return bool
     */
    public function checkIsBuiltInSnippet(string $snippetName): bool
    {
        if (\strpos($snippetName, Config::FASTLY_MAGENTO_MODULE . '_') !== 0) {
            return true;
        }

        foreach ($this->predefinedList as $disableName) {
            if (\strpos($snippetName, $disableName) === 0) {
                return true;
            }
        }
        return false;
    }
}
