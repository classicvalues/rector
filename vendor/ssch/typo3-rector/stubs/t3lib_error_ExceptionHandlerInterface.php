<?php

namespace RectorPrefix20210828;

if (\interface_exists('t3lib_error_ExceptionHandlerInterface')) {
    return;
}
interface t3lib_error_ExceptionHandlerInterface
{
}
\class_alias('t3lib_error_ExceptionHandlerInterface', 't3lib_error_ExceptionHandlerInterface', \false);
