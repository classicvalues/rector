<?php

namespace RectorPrefix20210828;

if (\class_exists('Tx_Extbase_Validation_Exception_InvalidValidationConfiguration')) {
    return;
}
class Tx_Extbase_Validation_Exception_InvalidValidationConfiguration
{
}
\class_alias('Tx_Extbase_Validation_Exception_InvalidValidationConfiguration', 'Tx_Extbase_Validation_Exception_InvalidValidationConfiguration', \false);
