<?php

namespace RectorPrefix20210828;

if (\class_exists('Tx_Extbase_MVC_Web_BackendRequestHandler')) {
    return;
}
class Tx_Extbase_MVC_Web_BackendRequestHandler
{
}
\class_alias('Tx_Extbase_MVC_Web_BackendRequestHandler', 'Tx_Extbase_MVC_Web_BackendRequestHandler', \false);
