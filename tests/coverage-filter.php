<?php

// Apply before Composer loads classes; avoid instrumenting vendor code.
if (function_exists('xdebug_set_filter')) {
    xdebug_set_filter(XDEBUG_FILTER_CODE_COVERAGE, XDEBUG_PATH_INCLUDE, [dirname(__DIR__).'/app/']);
}
