<?php

namespace RectorPrefix20210828;

if (\class_exists('Tx_Extbase_Validation_Validator_NumberValidator')) {
    return;
}
class Tx_Extbase_Validation_Validator_NumberValidator
{
}
\class_alias('Tx_Extbase_Validation_Validator_NumberValidator', 'Tx_Extbase_Validation_Validator_NumberValidator', \false);
