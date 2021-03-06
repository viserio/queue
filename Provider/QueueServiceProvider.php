<?php
declare(strict_types=1);
namespace Viserio\Component\Queue\Provider;

use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Viserio\Component\Contract\Config\Repository as RepositoryContract;
use Viserio\Component\Contract\Encryption\Encrypter as EncrypterContract;
use Viserio\Component\Queue\QueueManager;

class QueueServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFactories(): array
    {
        return [
            QueueManager::class => [self::class, 'createQueueManager'],
            'queue'             => function (ContainerInterface $container) {
                return $container->get(QueueManager::class);
            },
            'queue.connection' => [self::class, 'createQueueConnection'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(): array
    {
        return [];
    }

    public static function createQueueManager(ContainerInterface $container): QueueManager
    {
        return new QueueManager(
            $container,
            $container->get(EncrypterContract::class)
        );
    }

    public static function createQueueConnection(ContainerInterface $container)
    {
        return $container->get(RepositoryContract::class)->connection();
    }
}
