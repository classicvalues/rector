<?php

namespace RectorPrefix20210828;

if (\class_exists('tx_scheduler_RecyclerGarbageCollection')) {
    return;
}
class tx_scheduler_RecyclerGarbageCollection
{
}
\class_alias('tx_scheduler_RecyclerGarbageCollection', 'tx_scheduler_RecyclerGarbageCollection', \false);
