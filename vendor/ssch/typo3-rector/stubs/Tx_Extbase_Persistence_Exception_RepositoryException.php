<?php

namespace RectorPrefix20210828;

if (\class_exists('Tx_Extbase_Persistence_Exception_RepositoryException')) {
    return;
}
class Tx_Extbase_Persistence_Exception_RepositoryException
{
}
\class_alias('Tx_Extbase_Persistence_Exception_RepositoryException', 'Tx_Extbase_Persistence_Exception_RepositoryException', \false);
