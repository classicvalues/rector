<?php

namespace RectorPrefix20210828;

if (\class_exists('Tx_Extbase_Configuration_Exception_NoSuchOption')) {
    return;
}
class Tx_Extbase_Configuration_Exception_NoSuchOption
{
}
\class_alias('Tx_Extbase_Configuration_Exception_NoSuchOption', 'Tx_Extbase_Configuration_Exception_NoSuchOption', \false);
