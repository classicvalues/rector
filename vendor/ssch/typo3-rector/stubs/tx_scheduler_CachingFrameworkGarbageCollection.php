<?php

namespace RectorPrefix20210828;

if (\class_exists('tx_scheduler_CachingFrameworkGarbageCollection')) {
    return;
}
class tx_scheduler_CachingFrameworkGarbageCollection
{
}
\class_alias('tx_scheduler_CachingFrameworkGarbageCollection', 'tx_scheduler_CachingFrameworkGarbageCollection', \false);
