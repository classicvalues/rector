<?php

declare (strict_types=1);
namespace Rector\Core\FileSystem;

use RectorPrefix20210828\Nette\Utils\Strings;
use Rector\Caching\UnchangedFilesFilter;
use RectorPrefix20210828\Symfony\Component\Finder\Finder;
use RectorPrefix20210828\Symfony\Component\Finder\SplFileInfo;
use RectorPrefix20210828\Symplify\Skipper\SkipCriteriaResolver\SkippedPathsResolver;
use RectorPrefix20210828\Symplify\SmartFileSystem\FileSystemFilter;
use RectorPrefix20210828\Symplify\SmartFileSystem\Finder\FinderSanitizer;
use Symplify\SmartFileSystem\SmartFileInfo;
/**
 * @see \Rector\Core\Tests\FileSystem\FilesFinder\FilesFinderTest
 */
final class FilesFinder
{
    /**
     * @var string
     * @see https://regex101.com/r/e1jm7v/1
     */
    private const STARTS_WITH_ASTERISK_REGEX = '#^\\*(.*?)[^*]$#';
    /**
     * @var string
     * @see https://regex101.com/r/EgJQyZ/1
     */
    private const ENDS_WITH_ASTERISK_REGEX = '#^[^*](.*?)\\*$#';
    /**
     * @var \Rector\Core\FileSystem\FilesystemTweaker
     */
    private $filesystemTweaker;
    /**
     * @var \Symplify\SmartFileSystem\Finder\FinderSanitizer
     */
    private $finderSanitizer;
    /**
     * @var \Symplify\SmartFileSystem\FileSystemFilter
     */
    private $fileSystemFilter;
    /**
     * @var \Symplify\Skipper\SkipCriteriaResolver\SkippedPathsResolver
     */
    private $skippedPathsResolver;
    /**
     * @var \Rector\Caching\UnchangedFilesFilter
     */
    private $unchangedFilesFilter;
    public function __construct(\Rector\Core\FileSystem\FilesystemTweaker $filesystemTweaker, \RectorPrefix20210828\Symplify\SmartFileSystem\Finder\FinderSanitizer $finderSanitizer, \RectorPrefix20210828\Symplify\SmartFileSystem\FileSystemFilter $fileSystemFilter, \RectorPrefix20210828\Symplify\Skipper\SkipCriteriaResolver\SkippedPathsResolver $skippedPathsResolver, \Rector\Caching\UnchangedFilesFilter $unchangedFilesFilter)
    {
        $this->filesystemTweaker = $filesystemTweaker;
        $this->finderSanitizer = $finderSanitizer;
        $this->fileSystemFilter = $fileSystemFilter;
        $this->skippedPathsResolver = $skippedPathsResolver;
        $this->unchangedFilesFilter = $unchangedFilesFilter;
    }
    /**
     * @param string[] $source
     * @param string[] $suffixes
     * @return SmartFileInfo[]
     */
    public function findInDirectoriesAndFiles(array $source, array $suffixes = []) : array
    {
        $filesAndDirectories = $this->filesystemTweaker->resolveWithFnmatch($source);
        $filePaths = $this->fileSystemFilter->filterFiles($filesAndDirectories);
        $directories = $this->fileSystemFilter->filterDirectories($filesAndDirectories);
        $smartFileInfos = [];
        foreach ($filePaths as $filePath) {
            $smartFileInfos[] = new \Symplify\SmartFileSystem\SmartFileInfo($filePath);
        }
        $smartFileInfos = $this->unchangedFilesFilter->filterAndJoinWithDependentFileInfos($smartFileInfos);
        return \array_merge($smartFileInfos, $this->findInDirectories($directories, $suffixes));
    }
    /**
     * @param string[] $directories
     * @param string[] $suffixes
     * @return SmartFileInfo[]
     */
    private function findInDirectories(array $directories, array $suffixes) : array
    {
        if ($directories === []) {
            return [];
        }
        $finder = \RectorPrefix20210828\Symfony\Component\Finder\Finder::create()->followLinks()->files()->size('> 0')->in($directories)->sortByName();
        if ($suffixes !== []) {
            $suffixesPattern = $this->normalizeSuffixesToPattern($suffixes);
            $finder->name($suffixesPattern);
        }
        $this->addFilterWithExcludedPaths($finder);
        $smartFileInfos = $this->finderSanitizer->sanitize($finder);
        return $this->unchangedFilesFilter->filterAndJoinWithDependentFileInfos($smartFileInfos);
    }
    /**
     * @param string[] $suffixes
     */
    private function normalizeSuffixesToPattern(array $suffixes) : string
    {
        $suffixesPattern = \implode('|', $suffixes);
        return '#\\.(' . $suffixesPattern . ')$#';
    }
    private function addFilterWithExcludedPaths(\RectorPrefix20210828\Symfony\Component\Finder\Finder $finder) : void
    {
        $excludePaths = $this->skippedPathsResolver->resolve();
        if ($excludePaths === []) {
            return;
        }
        $finder->filter(function (\RectorPrefix20210828\Symfony\Component\Finder\SplFileInfo $splFileInfo) use($excludePaths) : bool {
            /** @var string|false $realPath */
            $realPath = $splFileInfo->getRealPath();
            if (!$realPath) {
                //dead symlink
                return \false;
            }
            // make the path work accross different OSes
            $realPath = \str_replace('\\', '/', $realPath);
            // return false to remove file
            foreach ($excludePaths as $excludePath) {
                // make the path work accross different OSes
                $excludePath = \str_replace('\\', '/', $excludePath);
                if (\RectorPrefix20210828\Nette\Utils\Strings::match($realPath, '#' . \preg_quote($excludePath, '#') . '#')) {
                    return \false;
                }
                $excludePath = $this->normalizeForFnmatch($excludePath);
                if (\fnmatch($excludePath, $realPath)) {
                    return \false;
                }
            }
            return \true;
        });
    }
    /**
     * "value*" → "*value*"
     * "*value" → "*value*"
     */
    private function normalizeForFnmatch(string $path) : string
    {
        // ends with *
        if (\RectorPrefix20210828\Nette\Utils\Strings::match($path, self::ENDS_WITH_ASTERISK_REGEX)) {
            return '*' . $path;
        }
        // starts with *
        if (\RectorPrefix20210828\Nette\Utils\Strings::match($path, self::STARTS_WITH_ASTERISK_REGEX)) {
            return $path . '*';
        }
        return $path;
    }
}
