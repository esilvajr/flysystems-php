<?php


namespace Arquivei\Flysystems\GoogleCloudStorage;

use Arquivei\Flysystems\GoogleCloudStorage\Adapters\GoogleStorageAdapter;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Filesystem;

class GoogleCloudStorageProvider extends ServiceProvider
{
    /**
     * Create a Filesystem instance with the given adapter.
     *
     * @param  \League\Flysystem\AdapterInterface  $adapter
     * @param  array  $config
     * @return \League\Flysystem\FilesystemInterface
     */
    protected function createFilesystem(AdapterInterface $adapter, array $config)
    {
        $cache = Arr::pull($config, 'cache');

        $config = Arr::only($config, ['visibility', 'disable_asserts', 'url']);

        if ($cache) {
            $adapter = new CachedAdapter($adapter, $this->createCacheStore($cache));
        }

        return new Filesystem($adapter, count($config) > 0 ? $config : null);
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $factory = $this->app->make('filesystem'); /* @var FilesystemManager $factory */

        $factory->extend('gcs', function ($app, $config) {
            $storageClient = new StorageClient([
                'projectId' => $config['project_id'],
                'keyFilePath' => array_get($config, 'key_file'),
            ]);
            $bucket = $storageClient->bucket($config['bucket']);
            $pathPrefix = array_get($config, 'path_prefix');

            $adapter = new GoogleStorageAdapter($storageClient, $bucket, $pathPrefix, $config);
            return $this->createFilesystem($adapter, $config);
        });
    }


    /**
     * Register bindings in the container.
     */
    public function register()
    {
        //
    }
}