<?php

namespace TheCodingMachine;

use Interop\Container\ContainerInterface;
use Simplex\Container;
use Twig_Environment;
use Twig_Loader_Chain;
use Twig_Loader_Filesystem;
use Twig_LoaderInterface;

class TwigServiceProvider
{
    /**
     * @var string
     */
    protected $twig_dir;

    /**
     * @var string
     */
    protected $cache_dir;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @param string      $twig_dir  absolute path to the Twig templates-directory
     * @param string|null $cache_dir absolute path to the Twig cache-directory (or NULL to use a system temp dir)
     * @param bool        $debug     TRUE to bootstrap Twig in debug-mode
     */
    public function __construct(string $twig_dir, string $cache_dir = null, bool $debug = true)
    {
        $this->twig_dir = $twig_dir;
        $this->cache_dir = $cache_dir ?: $this->createDefaultCacheDir();
        $this->debug = $debug;
    }

    /**
     * @param Container $container Simplex Container instance to bootstrap
     */
    public function bootstrap(Container $container)
    {
        $container[Twig_Environment::class] = function(ContainerInterface $container) {
            return $this->createTwigEnvironment($container);
        };

        $container[Twig_LoaderInterface::class] = function (ContainerInterface $container) {
            return $container->get(Twig_Loader_Chain::class);
        };

        $container[Twig_Loader_Chain::class] = function(ContainerInterface $container) {
            return $this->createLoaderChain($container);
        };

        $container['twig_options'] = function (ContainerInterface $container) {
            return $this->createOptions($container);
        };

        $container['twig_loaders'] = function (ContainerInterface $container) {
            return $this->createLoadersArray($container);
        };

        $container[Twig_Loader_Filesystem::class] = function (ContainerInterface $container) {
            return $this->createTwigLoaderFilesystem($container);
        };

        $container['twig_directory'] = $this->twig_dir;

        $container['twig_cache_directory'] = $this->cache_dir;

        $container['twig_extensions'] = [];
    }

    protected function createTwigEnvironment(ContainerInterface $container) : Twig_Environment
    {
        $environment = new Twig_Environment($container->get(\Twig_LoaderInterface::class), $container->get('twig_options'));

        $environment->setExtensions($container->get('twig_extensions'));

        return $environment;
    }

    protected function createOptions(ContainerInterface $container) : array
    {
        return [
            'debug'       => $this->debug,
            'auto_reload' => true,
            'cache'       => $this->cache_dir,
        ];
    }

    protected function createLoaderChain(ContainerInterface $container) : Twig_Loader_Chain
    {
        return new Twig_Loader_Chain($container->get('twig_loaders'));
    }

    protected function createLoadersArray(ContainerInterface $container) : array
    {
        return [
            $container->get(Twig_Loader_Filesystem::class),
        ];
    }

    protected function createTwigLoaderFilesystem(ContainerInterface $container) : Twig_Loader_Filesystem
    {
        return new Twig_Loader_Filesystem($container->get('twig_directory'));
    }

    protected function createDefaultCacheDir() : string
    {
        // If we are running on a Unix environment, let's prepend the cache with the user id of the PHP process.
        // This way, we can avoid rights conflicts.
        if (function_exists('posix_geteuid')) {
            $posixGetuid = posix_geteuid();
        } else {
            $posixGetuid = '';
        }

        return rtrim(sys_get_temp_dir(), '/\\').'/twig_compiled_cache_'.$posixGetuid.str_replace(':', '', dirname(__DIR__, 4));
    }
}
