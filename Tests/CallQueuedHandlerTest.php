<?php
declare(strict_types=1);
namespace Viserio\Component\Queue\Tests;

use Narrowspark\TestingHelper\ArrayContainer;
use Narrowspark\TestingHelper\Phpunit\MockeryTestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Viserio\Component\Bus\QueueingDispatcher;
use Viserio\Component\Contract\Encryption\Encrypter as EncrypterContract;
use Viserio\Component\Contract\Queue\Job as JobContract;
use Viserio\Component\Queue\CallQueuedHandler;
use Viserio\Component\Queue\Connector\RedisQueue;
use Viserio\Component\Queue\Job\RedisJob;
use Viserio\Component\Queue\Tests\Fixture\InteractsWithQueue;

/**
 * @internal
 */
final class CallQueuedHandlerTest extends MockeryTestCase
{
    public function testCall(): void
    {
        $command = \serialize(new stdClass());

        $job = $this->mock(JobContract::class);
        $job->shouldReceive('isDeletedOrReleased')
            ->once()
            ->andReturn(false);
        $job->shouldReceive('delete')
            ->once();

        $encrypter = $this->mock(EncrypterContract::class);
        $encrypter->shouldReceive('decrypt')
            ->once()
            ->with($command)
            ->andReturn($command);

        $container = new ArrayContainer();
        $handler   = $this->mock(stdClass::class);
        $handler->shouldReceive('handle')
            ->once()
            ->andReturn('foo');

        $container->set('stdClass', $handler);

        $dispatcher = new QueueingDispatcher($container);
        $dispatcher->mapUsing(function () {
            return 'stdClass@handle';
        });

        $callHandler = new CallQueuedHandler(
            $dispatcher,
            $encrypter
        );

        $callHandler->call($job, ['command' => $command]);
    }

    public function testFailed(): void
    {
        $redisContainer = $this->mock(ContainerInterface::class);
        $redisContainer->shouldReceive('get');

        $job = new RedisJob(
            $redisContainer,
            $this->mock(RedisQueue::class),
            \json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]),
            \json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]),
            'default'
        );

        $command = \serialize($job);

        $encrypter = $this->mock(EncrypterContract::class);
        $encrypter->shouldReceive('decrypt')
            ->once()
            ->with($command)
            ->andReturn($command);

        $dispatcher = new QueueingDispatcher(new ArrayContainer());
        $dispatcher->mapUsing(function () {
            return 'stdClass@handle';
        });

        $callHandler = new CallQueuedHandler(
            $dispatcher,
            $encrypter
        );

        $callHandler->failed(['command' => $command]);
    }

    public function testCallWithInteractsWithQueue(): void
    {
        $command = \serialize(new InteractsWithQueue());

        $job = $this->mock(JobContract::class);
        $job->shouldReceive('isDeletedOrReleased')
            ->once()
            ->andReturn(false);
        $job->shouldReceive('delete')
            ->once();

        $encrypter = $this->mock(EncrypterContract::class);
        $encrypter->shouldReceive('decrypt')
            ->once()
            ->with($command)
            ->andReturn($command);

        $container = new ArrayContainer();
        $handler   = $this->mock(stdClass::class);
        $handler->shouldReceive('handle')
            ->once()
            ->andReturn('foo');

        $container->set('stdClass', $handler);

        $dispatcher = new QueueingDispatcher($container);
        $dispatcher->mapUsing(function () {
            return 'stdClass@handle';
        });

        $callHandler = new CallQueuedHandler(
            $dispatcher,
            $encrypter
        );

        $callHandler->call($job, ['command' => $command]);
    }
}
