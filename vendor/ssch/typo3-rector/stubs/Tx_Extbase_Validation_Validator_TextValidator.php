<?php

namespace RectorPrefix20210828;

if (\class_exists('Tx_Extbase_Validation_Validator_TextValidator')) {
    return;
}
class Tx_Extbase_Validation_Validator_TextValidator
{
}
\class_alias('Tx_Extbase_Validation_Validator_TextValidator', 'Tx_Extbase_Validation_Validator_TextValidator', \false);
