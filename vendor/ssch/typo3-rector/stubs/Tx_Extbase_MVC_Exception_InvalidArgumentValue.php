<?php

namespace RectorPrefix20210828;

if (\class_exists('Tx_Extbase_MVC_Exception_InvalidArgumentValue')) {
    return;
}
class Tx_Extbase_MVC_Exception_InvalidArgumentValue
{
}
\class_alias('Tx_Extbase_MVC_Exception_InvalidArgumentValue', 'Tx_Extbase_MVC_Exception_InvalidArgumentValue', \false);
