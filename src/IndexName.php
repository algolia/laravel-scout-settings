<?php

namespace Algolia\Settings;

final class IndexName
{
    /**
     * @var string
     */
    private $remote;
    /**
     * @var string
     */
    private $local;
    /**
     * @var bool
     */
    private $usePrefix;

    /**
     * @param string $remote
     * @param bool $usePrefix
     */
    private function __construct($remote, $usePrefix)
    {
        $this->remote    = $remote;
        $this->usePrefix = $usePrefix;
    }

    /**
     * @param string $remoteName
     * @param bool $usePrefix
     *
     * @return static
     */
    public static function createFromRemote($remoteName, $usePrefix = true)
    {
        return new static($remoteName, $usePrefix);
    }

    /**
     * @param string $localName
     * @param bool $usePrefix
     *
     * @return static
     */
    public static function createFromLocal($localName, $usePrefix = true)
    {
        return new static(
            config('scout.prefix') . $localName,
            $usePrefix
        );
    }

    /**
     * @return string
     */
    public function local()
    {
        if ($this->local) {
            return $this->local;
        }

        if ($this->usePrefix) {
            return $this->local = $this->remote;
        }

        return $this->local = preg_replace(
            '/^' . preg_quote(config('scout.prefix'), '/') . '/',
            '',
            $this->remote
        );
    }

    /**
     * @return string
     */
    public function remote()
    {
        return $this->remote;
    }

    public function usesPrefix()
    {
        return $this->usePrefix;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->remote();
    }
}
