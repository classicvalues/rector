<?php

declare (strict_types=1);
namespace Rector\Core\Configuration;

use Rector\ChangesReporting\Output\ConsoleOutputFormatter;
use Rector\Core\ValueObject\Configuration;
use RectorPrefix20210828\Symfony\Component\Console\Input\InputInterface;
use RectorPrefix20210828\Symfony\Component\Console\Style\SymfonyStyle;
use RectorPrefix20210828\Symplify\PackageBuilder\Parameter\ParameterProvider;
final class ConfigurationFactory
{
    /**
     * @var \Symplify\PackageBuilder\Parameter\ParameterProvider
     */
    private $parameterProvider;
    /**
     * @var \Symfony\Component\Console\Style\SymfonyStyle
     */
    private $symfonyStyle;
    public function __construct(\RectorPrefix20210828\Symplify\PackageBuilder\Parameter\ParameterProvider $parameterProvider, \RectorPrefix20210828\Symfony\Component\Console\Style\SymfonyStyle $symfonyStyle)
    {
        $this->parameterProvider = $parameterProvider;
        $this->symfonyStyle = $symfonyStyle;
    }
    public function createForTests() : \Rector\Core\ValueObject\Configuration
    {
        $fileExtensions = $this->parameterProvider->provideArrayParameter(\Rector\Core\Configuration\Option::FILE_EXTENSIONS);
        return new \Rector\Core\ValueObject\Configuration(\true, \true, \false, 'console', $fileExtensions);
    }
    /**
     * Needs to run in the start of the life cycle, since the rest of workflow uses it.
     */
    public function createFromInput(\RectorPrefix20210828\Symfony\Component\Console\Input\InputInterface $input) : \Rector\Core\ValueObject\Configuration
    {
        $isDryRun = (bool) $input->getOption(\Rector\Core\Configuration\Option::DRY_RUN);
        $shouldClearCache = (bool) $input->getOption(\Rector\Core\Configuration\Option::CLEAR_CACHE);
        $outputFormat = (string) $input->getOption(\Rector\Core\Configuration\Option::OUTPUT_FORMAT);
        $showProgressBar = $this->shouldShowProgressBar($input, $outputFormat);
        $showDiffs = !(bool) $input->getOption(\Rector\Core\Configuration\Option::NO_DIFFS);
        $paths = $this->resolvePaths($input);
        $fileExtensions = $this->parameterProvider->provideArrayParameter(\Rector\Core\Configuration\Option::FILE_EXTENSIONS);
        return new \Rector\Core\ValueObject\Configuration($isDryRun, $showProgressBar, $shouldClearCache, $outputFormat, $fileExtensions, $paths, $showDiffs);
    }
    private function shouldShowProgressBar(\RectorPrefix20210828\Symfony\Component\Console\Input\InputInterface $input, string $outputFormat) : bool
    {
        $noProgressBar = (bool) $input->getOption(\Rector\Core\Configuration\Option::NO_PROGRESS_BAR);
        if ($noProgressBar) {
            return \false;
        }
        if ($this->symfonyStyle->isVerbose()) {
            return \false;
        }
        return $outputFormat === \Rector\ChangesReporting\Output\ConsoleOutputFormatter::NAME;
    }
    /**
     * @param string[] $commandLinePaths
     * @return string[]
     */
    private function correctBashSpacePaths(array $commandLinePaths) : array
    {
        // fixes bash edge-case that to merges string with space to one
        foreach ($commandLinePaths as $commandLinePath) {
            if (\strpos($commandLinePath, ' ') !== \false) {
                $commandLinePaths = \explode(' ', $commandLinePath);
            }
        }
        return $commandLinePaths;
    }
    /**
     * @return string[]|mixed[]
     */
    private function resolvePaths(\RectorPrefix20210828\Symfony\Component\Console\Input\InputInterface $input) : array
    {
        $commandLinePaths = (array) $input->getArgument(\Rector\Core\Configuration\Option::SOURCE);
        // command line has priority
        if ($commandLinePaths !== []) {
            return $this->correctBashSpacePaths($commandLinePaths);
        }
        // fallback to parameter
        return $this->parameterProvider->provideArrayParameter(\Rector\Core\Configuration\Option::PATHS);
    }
}
