<?php

namespace RectorPrefix20210828;

if (\class_exists('Tx_Extbase_Configuration_Exception_NoSuchFile')) {
    return;
}
class Tx_Extbase_Configuration_Exception_NoSuchFile
{
}
\class_alias('Tx_Extbase_Configuration_Exception_NoSuchFile', 'Tx_Extbase_Configuration_Exception_NoSuchFile', \false);
