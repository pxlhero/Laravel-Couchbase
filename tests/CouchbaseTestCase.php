<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Connectors\ConnectionFactory;

/**
 * Class CouchbaseTestCase
 */
class CouchbaseTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var \Illuminate\Container\Container $app */
    protected $app;

    protected function setUp()
    {
        $this->createApplicationContainer();
    }

    /**
     * @return \Illuminate\Config\Repository
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function registerConfigure()
    {
        $filesystem = new \Illuminate\Filesystem\Filesystem;
        $this->app['config']->set(
            "database",
            $filesystem->getRequire(__DIR__ . '/config/database.php')
        );
        $this->app['config']->set(
            "cache",
            $filesystem->getRequire(__DIR__ . '/config/cache.php')
        );
        $this->app['config']->set(
            "session",
            $filesystem->getRequire(__DIR__ . '/config/session.php')
        );
        $this->app['config']->set(
            "queue",
            $filesystem->getRequire(__DIR__ . '/config/queue.php')
        );
        $this->app['config']->set(
            "app",
            $filesystem->getRequire(__DIR__ . '/config/app.php')
        );
        $this->app['files'] = $filesystem;
    }

    protected function registerDatabase()
    {
        Model::clearBootedModels();
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });
        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });
    }

    protected function registerCache()
    {
        $this->app->singleton('cache', function ($app) {
            return new \Illuminate\Cache\CacheManager($app);
        });
        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('memcached.connector', function () {
            return new \Illuminate\Cache\MemcachedConnector();
        });
    }

    protected function createApplicationContainer()
    {
        $this->app = new \TestContainer();

        $this->app->singleton('config', function () {
            return new \Illuminate\Config\Repository;
        });
        $this->registerConfigure();
        $eventServiceProvider = new \Illuminate\Encryption\EncryptionServiceProvider($this->app);
        $eventServiceProvider->register();
        $eventServiceProvider = new \Illuminate\Events\EventServiceProvider($this->app);
        $eventServiceProvider->register();
        $queueProvider = new \Illuminate\Queue\QueueServiceProvider($this->app);
        $queueProvider->register();
        $sessionProvider = new \Illuminate\Session\SessionServiceProvider($this->app);
        $sessionProvider->register();
        $this->registerDatabase();
        $this->registerCache();
        $couchbaseProvider = new \Ytake\LaravelCouchbase\CouchbaseServiceProvider($this->app);
        $couchbaseProvider->register();
        $couchbaseProvider->boot();
        $this->app->bind(
            \Illuminate\Container\Container::class,
            function () {
                return $this->app;
            }
        );
        (new \Illuminate\Events\EventServiceProvider($this->app))->register();
        \Illuminate\Container\Container::setInstance($this->app);
    }

    protected function tearDown()
    {
        $this->app = null;
    }

    /**
     * @param string $bucket
     *
     * @return CouchbaseClusterManager
     */
    protected function createBucket($bucket)
    {
        $cluster = new \CouchbaseCluster('127.0.0.1');
        $clusterManager = $cluster->manager('Administrator', 'Administrator');
        $clusterManager->createBucket($bucket,
            ['bucketType' => 'couchbase', 'saslPassword' => '', 'flushEnabled' => true]);
        sleep(5);
        return $clusterManager;
    }

    /**
     * @param CouchbaseClusterManager $clusterManager
     * @param string                  $bucket
     */
    protected function removeBucket(\CouchbaseClusterManager $clusterManager, $bucket)
    {
        $clusterManager->removeBucket($bucket);
    }
}

class TestContainer extends \Illuminate\Container\Container
{
    public function version()
    {
        return '5.2.1';
    }
}