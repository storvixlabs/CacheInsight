<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Model;

class ActionMatcher
{
    /**
     * Matches an action name against a comma-separated list of names or patterns with * wildcards.
     */
    public static function matches(string $actionName, string $patterns): bool
    {
        foreach (preg_split('/[\s,;]+/', $patterns, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $pattern) {
            if (fnmatch($pattern, $actionName)) {
                return true;
            }
        }

        return false;
    }
}
