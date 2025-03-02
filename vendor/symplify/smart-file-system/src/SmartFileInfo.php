<?php

declare (strict_types=1);
namespace RectorPrefix20210828\Symplify\SmartFileSystem;

use RectorPrefix20210828\Nette\Utils\Strings;
use RectorPrefix20210828\Symfony\Component\Finder\SplFileInfo;
use RectorPrefix20210828\Symplify\EasyTesting\PHPUnit\StaticPHPUnitEnvironment;
use RectorPrefix20210828\Symplify\EasyTesting\StaticFixtureSplitter;
use RectorPrefix20210828\Symplify\SmartFileSystem\Exception\DirectoryNotFoundException;
use RectorPrefix20210828\Symplify\SmartFileSystem\Exception\FileNotFoundException;
/**
 * @see \Symplify\SmartFileSystem\Tests\SmartFileInfo\SmartFileInfoTest
 */
final class SmartFileInfo extends \RectorPrefix20210828\Symfony\Component\Finder\SplFileInfo
{
    /**
     * @var string
     * @see https://regex101.com/r/SYP00O/1
     */
    private const LAST_SUFFIX_REGEX = '#\\.[^.]+$#';
    /**
     * @var \Symplify\SmartFileSystem\SmartFileSystem
     */
    private $smartFileSystem;
    public function __construct(string $filePath)
    {
        $this->smartFileSystem = new \RectorPrefix20210828\Symplify\SmartFileSystem\SmartFileSystem();
        // accepts also dirs
        if (!\file_exists($filePath)) {
            throw new \RectorPrefix20210828\Symplify\SmartFileSystem\Exception\FileNotFoundException(\sprintf('File path "%s" was not found while creating "%s" object.', $filePath, self::class));
        }
        // real path doesn't work in PHAR: https://www.php.net/manual/en/function.realpath.php
        if (\strncmp($filePath, 'phar://', \strlen('phar://')) === 0) {
            $relativeFilePath = $filePath;
            $relativeDirectoryPath = \dirname($filePath);
        } else {
            $realPath = \realpath($filePath);
            $relativeFilePath = \rtrim($this->smartFileSystem->makePathRelative($realPath, \getcwd()), '/');
            $relativeDirectoryPath = \dirname($relativeFilePath);
        }
        parent::__construct($filePath, $relativeDirectoryPath, $relativeFilePath);
    }
    public function getBasenameWithoutSuffix() : string
    {
        return \pathinfo($this->getFilename())['filename'];
    }
    public function getSuffix() : string
    {
        return \pathinfo($this->getFilename(), \PATHINFO_EXTENSION);
    }
    /**
     * @param string[] $suffixes
     */
    public function hasSuffixes($suffixes) : bool
    {
        return \in_array($this->getSuffix(), $suffixes, \true);
    }
    public function getRealPathWithoutSuffix() : string
    {
        return \RectorPrefix20210828\Nette\Utils\Strings::replace($this->getRealPath(), self::LAST_SUFFIX_REGEX, '');
    }
    public function getRelativeFilePath() : string
    {
        return $this->getRelativePathname();
    }
    public function getRelativeDirectoryPath() : string
    {
        return $this->getRelativePath();
    }
    /**
     * @param string $directory
     */
    public function getRelativeFilePathFromDirectory($directory) : string
    {
        if (!\file_exists($directory)) {
            throw new \RectorPrefix20210828\Symplify\SmartFileSystem\Exception\DirectoryNotFoundException(\sprintf('Directory "%s" was not found in %s.', $directory, self::class));
        }
        $relativeFilePath = $this->smartFileSystem->makePathRelative($this->getNormalizedRealPath(), (string) \realpath($directory));
        return \rtrim($relativeFilePath, '/');
    }
    public function getRelativeFilePathFromCwdInTests() : string
    {
        // special case for tests
        if (\RectorPrefix20210828\Symplify\EasyTesting\PHPUnit\StaticPHPUnitEnvironment::isPHPUnitRun()) {
            return $this->getRelativeFilePathFromDirectory(\RectorPrefix20210828\Symplify\EasyTesting\StaticFixtureSplitter::getTemporaryPath());
        }
        return $this->getRelativeFilePathFromDirectory(\getcwd());
    }
    public function getRelativeFilePathFromCwd() : string
    {
        return $this->getRelativeFilePathFromDirectory(\getcwd());
    }
    /**
     * @param string $string
     */
    public function endsWith($string) : bool
    {
        return \substr_compare($this->getNormalizedRealPath(), $string, -\strlen($string)) === 0;
    }
    /**
     * @param string $string
     */
    public function doesFnmatch($string) : bool
    {
        $normalizedPath = $this->normalizePath($string);
        if (\fnmatch($normalizedPath, $this->getNormalizedRealPath())) {
            return \true;
        }
        // in case of relative compare
        return \fnmatch('*/' . $this->normalizePath($string), $this->getNormalizedRealPath());
    }
    public function getRealPath() : string
    {
        // for phar compatibility @see https://github.com/rectorphp/rector/commit/e5d7cee69558f7e6b35d995a5ca03fa481b0407c
        return parent::getRealPath() ?: $this->getPathname();
    }
    public function getRealPathDirectory() : string
    {
        return \dirname($this->getRealPath());
    }
    /**
     * @param string $partialPath
     */
    public function startsWith($partialPath) : bool
    {
        return \strncmp($this->getNormalizedRealPath(), $partialPath, \strlen($partialPath)) === 0;
    }
    private function getNormalizedRealPath() : string
    {
        return $this->normalizePath($this->getRealPath());
    }
    private function normalizePath(string $path) : string
    {
        return \str_replace('\\', '/', $path);
    }
}
/**
 * @see \Symplify\SmartFileSystem\Tests\SmartFileInfo\SmartFileInfoTest
 */
\class_alias('RectorPrefix20210828\\Symplify\\SmartFileSystem\\SmartFileInfo', 'Symplify\\SmartFileSystem\\SmartFileInfo', \false);
