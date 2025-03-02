<?php

declare (strict_types=1);
namespace RectorPrefix20210828;

use RectorPrefix20210828\Nette\Utils\Json;
use Rector\ChangesReporting\Output\JsonOutputFormatter;
use Rector\Core\Bootstrap\RectorConfigsResolver;
use Rector\Core\Configuration\Option;
use Rector\Core\Console\ConsoleApplication;
use Rector\Core\Console\Style\SymfonyStyleFactory;
use Rector\Core\DependencyInjection\RectorContainerFactory;
use Rector\Core\HttpKernel\RectorKernel;
use RectorPrefix20210828\Symfony\Component\Console\Command\Command;
use RectorPrefix20210828\Symfony\Component\Console\Input\ArgvInput;
use RectorPrefix20210828\Symplify\PackageBuilder\Reflection\PrivatesCaller;
// @ intentionally: continue anyway
@\ini_set('memory_limit', '-1');
// Performance boost
\error_reporting(\E_ALL);
\ini_set('display_errors', 'stderr');
\gc_disable();
\define('__RECTOR_RUNNING__', \true);
// Require Composer autoload.php
$autoloadIncluder = new \RectorPrefix20210828\AutoloadIncluder();
$autoloadIncluder->includeDependencyOrRepositoryVendorAutoloadIfExists();
// load extracted PHPStan with its own preload.php
$extractedPhpstanAutoload = __DIR__ . '/../vendor/phpstan/phpstan-extracted/vendor/autoload.php';
if (\file_exists($extractedPhpstanAutoload)) {
    require_once $extractedPhpstanAutoload;
} elseif (\RectorPrefix20210828\should_include_preload()) {
    require_once __DIR__ . '/../preload.php';
}
require_once __DIR__ . '/../src/constants.php';
// pre-set for PHP 5.6/7.0 downgraded version
$autoloadIncluder->loadIfExistsAndNotLoadedYet(__DIR__ . '/../vendor/phpstan/phpstan-extracted/vendor/phpstan-autoload.php');
$autoloadIncluder->loadIfExistsAndNotLoadedYet(__DIR__ . '/../vendor/scoper-autoload.php');
$autoloadIncluder->autoloadProjectAutoloaderFile();
$autoloadIncluder->autoloadFromCommandLine();
$rectorConfigsResolver = new \Rector\Core\Bootstrap\RectorConfigsResolver();
try {
    $bootstrapConfigs = $rectorConfigsResolver->provide();
    $rectorContainerFactory = new \Rector\Core\DependencyInjection\RectorContainerFactory();
    $container = $rectorContainerFactory->createFromBootstrapConfigs($bootstrapConfigs);
} catch (\Throwable $throwable) {
    // for json output
    $argvInput = new \RectorPrefix20210828\Symfony\Component\Console\Input\ArgvInput();
    $outputFormat = $argvInput->getParameterOption('--' . \Rector\Core\Configuration\Option::OUTPUT_FORMAT);
    // report fatal error in json format
    if ($outputFormat === \Rector\ChangesReporting\Output\JsonOutputFormatter::NAME) {
        echo \RectorPrefix20210828\Nette\Utils\Json::encode(['fatal_errors' => [$throwable->getMessage()]]);
    } else {
        // report fatal errors in console format
        $symfonyStyleFactory = new \Rector\Core\Console\Style\SymfonyStyleFactory(new \RectorPrefix20210828\Symplify\PackageBuilder\Reflection\PrivatesCaller());
        $symfonyStyle = $symfonyStyleFactory->create();
        $symfonyStyle->error($throwable->getMessage());
    }
    exit(\RectorPrefix20210828\Symfony\Component\Console\Command\Command::FAILURE);
}
/** @var ConsoleApplication $application */
$application = $container->get(\Rector\Core\Console\ConsoleApplication::class);
exit($application->run());
final class AutoloadIncluder
{
    /**
     * @var string[]
     */
    private $alreadyLoadedAutoloadFiles = [];
    public function includeDependencyOrRepositoryVendorAutoloadIfExists() : void
    {
        // Rector's vendor is already loaded
        if (\class_exists(\Rector\Core\HttpKernel\RectorKernel::class)) {
            return;
        }
        // in Rector develop repository
        $this->loadIfExistsAndNotLoadedYet(__DIR__ . '/../vendor/autoload.php');
    }
    /**
     * In case Rector is installed as vendor dependency,
     * this autoloads the project vendor/autoload.php, including Rector
     */
    public function autoloadProjectAutoloaderFile() : void
    {
        $this->loadIfExistsAndNotLoadedYet(__DIR__ . '/../../../autoload.php');
    }
    public function autoloadFromCommandLine() : void
    {
        $cliArgs = $_SERVER['argv'];
        $autoloadOptionPosition = \array_search('-a', $cliArgs, \true) ?: \array_search('--autoload-file', $cliArgs, \true);
        if (!$autoloadOptionPosition) {
            return;
        }
        $autoloadFileValuePosition = $autoloadOptionPosition + 1;
        $fileToAutoload = $cliArgs[$autoloadFileValuePosition] ?? null;
        if ($fileToAutoload === null) {
            return;
        }
        $this->loadIfExistsAndNotLoadedYet($fileToAutoload);
    }
    public function loadIfExistsAndNotLoadedYet(string $filePath) : void
    {
        // the scoper-autoload.php is exists in phpstan-extracted/vendor/scoper-autoload.php, move the check in :
        if (!\file_exists($filePath)) {
            return;
        }
        if (\in_array($filePath, $this->alreadyLoadedAutoloadFiles, \true)) {
            return;
        }
        $this->alreadyLoadedAutoloadFiles[] = \realpath($filePath);
        require_once $filePath;
    }
}
\class_alias('RectorPrefix20210828\\AutoloadIncluder', 'AutoloadIncluder', \false);
// load local php-parser only in prefixed version or development repository
function should_include_preload() : bool
{
    if (\file_exists(__DIR__ . '/../vendor/scoper-autoload.php')) {
        return \true;
    }
    if (!\file_exists(\getcwd() . '/composer.json')) {
        return \false;
    }
    $composerJsonFileContent = \file_get_contents(\getcwd() . '/composer.json');
    return \strpos($composerJsonFileContent, '"name": "rector/rector"') !== \false;
}
