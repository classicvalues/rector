<?php

namespace RectorPrefix20210828;

if (\class_exists('tx_impexp')) {
    return;
}
class tx_impexp
{
}
\class_alias('tx_impexp', 'tx_impexp', \false);
