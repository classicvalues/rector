<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210828\Symfony\Component\HttpKernel\Profiler;

/**
 * Storage for profiler using files.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 */
class FileProfilerStorage implements \RectorPrefix20210828\Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface
{
    /**
     * Folder where profiler data are stored.
     *
     * @var string
     */
    private $folder;
    /**
     * Constructs the file storage using a "dsn-like" path.
     *
     * Example : "file:/path/to/the/storage/folder"
     *
     * @throws \RuntimeException
     */
    public function __construct(string $dsn)
    {
        if (\strncmp($dsn, 'file:', \strlen('file:')) !== 0) {
            throw new \RuntimeException(\sprintf('Please check your configuration. You are trying to use FileStorage with an invalid dsn "%s". The expected format is "file:/path/to/the/storage/folder".', $dsn));
        }
        $this->folder = \substr($dsn, 5);
        if (!\is_dir($this->folder) && \false === @\mkdir($this->folder, 0777, \true) && !\is_dir($this->folder)) {
            throw new \RuntimeException(\sprintf('Unable to create the storage directory (%s).', $this->folder));
        }
    }
    /**
     * {@inheritdoc}
     * @param string|null $ip
     * @param string|null $url
     * @param int|null $limit
     * @param string|null $method
     * @param int|null $start
     * @param int|null $end
     * @param string|null $statusCode
     */
    public function find($ip, $url, $limit, $method, $start = null, $end = null, $statusCode = null) : array
    {
        $file = $this->getIndexFilename();
        if (!\file_exists($file)) {
            return [];
        }
        $file = \fopen($file, 'r');
        \fseek($file, 0, \SEEK_END);
        $result = [];
        while (\count($result) < $limit && ($line = $this->readLineFromFile($file))) {
            $values = \str_getcsv($line);
            [$csvToken, $csvIp, $csvMethod, $csvUrl, $csvTime, $csvParent, $csvStatusCode] = $values;
            $csvTime = (int) $csvTime;
            if ($ip && \strpos($csvIp, $ip) === \false || $url && \strpos($csvUrl, $url) === \false || $method && \strpos($csvMethod, $method) === \false || $statusCode && \strpos($csvStatusCode, $statusCode) === \false) {
                continue;
            }
            if (!empty($start) && $csvTime < $start) {
                continue;
            }
            if (!empty($end) && $csvTime > $end) {
                continue;
            }
            $result[$csvToken] = ['token' => $csvToken, 'ip' => $csvIp, 'method' => $csvMethod, 'url' => $csvUrl, 'time' => $csvTime, 'parent' => $csvParent, 'status_code' => $csvStatusCode];
        }
        \fclose($file);
        return \array_values($result);
    }
    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $flags = \FilesystemIterator::SKIP_DOTS;
        $iterator = new \RecursiveDirectoryIterator($this->folder, $flags);
        $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if (\is_file($file)) {
                \unlink($file);
            } else {
                \rmdir($file);
            }
        }
    }
    /**
     * {@inheritdoc}
     * @param string $token
     */
    public function read($token) : ?\RectorPrefix20210828\Symfony\Component\HttpKernel\Profiler\Profile
    {
        if (!$token || !\file_exists($file = $this->getFilename($token))) {
            return null;
        }
        if (\function_exists('gzcompress')) {
            $file = 'compress.zlib://' . $file;
        }
        return $this->createProfileFromData($token, \unserialize(\file_get_contents($file)));
    }
    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     * @param \Symfony\Component\HttpKernel\Profiler\Profile $profile
     */
    public function write($profile) : bool
    {
        $file = $this->getFilename($profile->getToken());
        $profileIndexed = \is_file($file);
        if (!$profileIndexed) {
            // Create directory
            $dir = \dirname($file);
            if (!\is_dir($dir) && \false === @\mkdir($dir, 0777, \true) && !\is_dir($dir)) {
                throw new \RuntimeException(\sprintf('Unable to create the storage directory (%s).', $dir));
            }
        }
        $profileToken = $profile->getToken();
        // when there are errors in sub-requests, the parent and/or children tokens
        // may equal the profile token, resulting in infinite loops
        $parentToken = $profile->getParentToken() !== $profileToken ? $profile->getParentToken() : null;
        $childrenToken = \array_filter(\array_map(function (\RectorPrefix20210828\Symfony\Component\HttpKernel\Profiler\Profile $p) use($profileToken) {
            return $profileToken !== $p->getToken() ? $p->getToken() : null;
        }, $profile->getChildren()));
        // Store profile
        $data = ['token' => $profileToken, 'parent' => $parentToken, 'children' => $childrenToken, 'data' => $profile->getCollectors(), 'ip' => $profile->getIp(), 'method' => $profile->getMethod(), 'url' => $profile->getUrl(), 'time' => $profile->getTime(), 'status_code' => $profile->getStatusCode()];
        $context = \stream_context_create();
        if (\function_exists('gzcompress')) {
            $file = 'compress.zlib://' . $file;
            \stream_context_set_option($context, 'zlib', 'level', 3);
        }
        if (\false === \file_put_contents($file, \serialize($data), 0, $context)) {
            return \false;
        }
        if (!$profileIndexed) {
            // Add to index
            if (\false === ($file = \fopen($this->getIndexFilename(), 'a'))) {
                return \false;
            }
            \fputcsv($file, [$profile->getToken(), $profile->getIp(), $profile->getMethod(), $profile->getUrl(), $profile->getTime(), $profile->getParentToken(), $profile->getStatusCode()]);
            \fclose($file);
        }
        return \true;
    }
    /**
     * Gets filename to store data, associated to the token.
     *
     * @return string The profile filename
     * @param string $token
     */
    protected function getFilename($token)
    {
        // Uses 4 last characters, because first are mostly the same.
        $folderA = \substr($token, -2, 2);
        $folderB = \substr($token, -4, 2);
        return $this->folder . '/' . $folderA . '/' . $folderB . '/' . $token;
    }
    /**
     * Gets the index filename.
     *
     * @return string The index filename
     */
    protected function getIndexFilename()
    {
        return $this->folder . '/index.csv';
    }
    /**
     * Reads a line in the file, backward.
     *
     * This function automatically skips the empty lines and do not include the line return in result value.
     *
     * @param resource $file The file resource, with the pointer placed at the end of the line to read
     *
     * @return mixed A string representing the line or null if beginning of file is reached
     */
    protected function readLineFromFile($file)
    {
        $line = '';
        $position = \ftell($file);
        if (0 === $position) {
            return null;
        }
        while (\true) {
            $chunkSize = \min($position, 1024);
            $position -= $chunkSize;
            \fseek($file, $position);
            if (0 === $chunkSize) {
                // bof reached
                break;
            }
            $buffer = \fread($file, $chunkSize);
            if (\false === ($upTo = \strrpos($buffer, "\n"))) {
                $line = $buffer . $line;
                continue;
            }
            $position += $upTo;
            $line = \substr($buffer, $upTo + 1) . $line;
            \fseek($file, \max(0, $position), \SEEK_SET);
            if ('' !== $line) {
                break;
            }
        }
        return '' === $line ? null : $line;
    }
    /**
     * @param string $token
     * @param mixed[] $data
     * @param \Symfony\Component\HttpKernel\Profiler\Profile|null $parent
     */
    protected function createProfileFromData($token, $data, $parent = null)
    {
        $profile = new \RectorPrefix20210828\Symfony\Component\HttpKernel\Profiler\Profile($token);
        $profile->setIp($data['ip']);
        $profile->setMethod($data['method']);
        $profile->setUrl($data['url']);
        $profile->setTime($data['time']);
        $profile->setStatusCode($data['status_code']);
        $profile->setCollectors($data['data']);
        if (!$parent && $data['parent']) {
            $parent = $this->read($data['parent']);
        }
        if ($parent) {
            $profile->setParent($parent);
        }
        foreach ($data['children'] as $token) {
            if (!$token || !\file_exists($file = $this->getFilename($token))) {
                continue;
            }
            if (\function_exists('gzcompress')) {
                $file = 'compress.zlib://' . $file;
            }
            $profile->addChild($this->createProfileFromData($token, \unserialize(\file_get_contents($file)), $profile));
        }
        return $profile;
    }
}
