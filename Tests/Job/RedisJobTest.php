<?php
declare(strict_types=1);
namespace Viserio\Component\Queue\Tests\Job;

use Narrowspark\TestingHelper\Phpunit\MockeryTestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Viserio\Component\Queue\Connector\RedisQueue;
use Viserio\Component\Queue\Job\RedisJob;

/**
 * @internal
 */
final class RedisJobTest extends MockeryTestCase
{
    public function testReleaseProperlyReleasesJobOntoRedis(): void
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteAndRelease')
            ->once()
            ->with('default', \json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]), 1);

        $job->release(1);
    }

    public function testRunProperlyCallsTheJobHandler(): void
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('get')
            ->once()
            ->with('foo')
            ->andReturn($handler = $this->mock(stdClass::class));

        $handler->shouldReceive('run')
            ->once()
            ->with($job, ['data']);

        $job->run();
    }

    public function testDeleteRemovesTheJobFromRedis(): void
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteReserved')
            ->once()
            ->with('default', \json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]));

        $job->delete();
    }

    protected function getJob()
    {
        return new RedisJob(
            $this->mock(ContainerInterface::class),
            $this->mock(RedisQueue::class),
            \json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]),
            \json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]),
            'default'
        );
    }
}
