<?php

namespace RectorPrefix20210828;

if (\class_exists('t3lib_cache_frontend_StringFrontend')) {
    return;
}
class t3lib_cache_frontend_StringFrontend
{
}
\class_alias('t3lib_cache_frontend_StringFrontend', 't3lib_cache_frontend_StringFrontend', \false);
