<?php

namespace RectorPrefix20210828;

if (\class_exists('t3lib_collection_Nameable')) {
    return;
}
class t3lib_collection_Nameable
{
}
\class_alias('t3lib_collection_Nameable', 't3lib_collection_Nameable', \false);
