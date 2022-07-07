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
        foreach ($this->predefinedList as $builtinSnippet) {
            if (\strpos($snippetName, $builtinSnippet) === 0) {
                return true;
            }
        }
        return false;
    }
}
