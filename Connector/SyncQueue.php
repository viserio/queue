<?php
declare(strict_types=1);
namespace Viserio\Component\Queue\Connector;

use ErrorException;
use ParseError;
use Throwable;
use TypeError;
use Viserio\Component\Contract\Queue\Job as JobContract;
use Viserio\Component\Queue\Job\SyncJob;

class SyncQueue extends AbstractQueue
{
    /**
     * {@inheritdoc}
     */
    public function push($job, $data = '', string $queue = null)
    {
        $queueJob = new SyncJob(
            $this->container,
            $this->createPayload($job, $data, $queue)
        );

        try {
            $this->raiseBeforeJobEvent($queueJob);

            $queueJob->run();

            $this->raiseAfterJobEvent($queueJob);
        } catch (Throwable $exception) {
            $this->raiseExceptionOccurredJobEvent(
                $queueJob,
                $this->getErrorException($exception)
            );
            $this->handleFailedJob($queueJob);

            throw $exception;
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function pushRaw(string $payload, string $queue = null, array $options = []): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', string $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * {@inheritdoc}
     */
    public function pop(string $queue = null): void
    {
    }

    /**
     * Raise the before queue job event.
     *
     * @param \Viserio\Component\Contract\Queue\Job $job
     */
    protected function raiseBeforeJobEvent(JobContract $job): void
    {
        if ($this->container->has('events')) {
            $this->container->get('events')->trigger(
                'viserio.job.processing',
                [
                    'connection' => 'sync',
                    'job'        => $job,
                    'data'       => \json_decode($job->getRawBody(), true),
                ]
            );
        }
    }

    /**
     * Raise the after queue job event.
     *
     * @param \Viserio\Component\Contract\Queue\Job $job
     */
    protected function raiseAfterJobEvent(JobContract $job): void
    {
        if ($this->container->has('events')) {
            $this->container->get('events')->trigger(
                'viserio.job.processed',
                [
                    'connection' => 'sync',
                    'job'        => $job,
                    'data'       => \json_decode($job->getRawBody(), true),
                ]
            );
        }
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param \Viserio\Component\Contract\Queue\Job $job
     * @param \Throwable                            $exception
     */
    protected function raiseExceptionOccurredJobEvent(JobContract $job, Throwable $exception): void
    {
        if ($this->container->has('events')) {
            $this->container->get('events')->trigger(
                'viserio.job.exception.occurred',
                [
                    'connection' => 'sync',
                    'job'        => $job,
                    'data'       => \json_decode($job->getRawBody(), true),
                    'exception'  => $exception,
                ]
            );
        }
    }

    /**
     * Handle the failed job.
     *
     * @param \Viserio\Component\Contract\Queue\Job $job
     */
    protected function handleFailedJob(JobContract $job): void
    {
        $job->failed();

        if ($this->container->has('events')) {
            $this->container->get('events')->trigger(
                'viserio.job.failed',
                [
                    'connection' => 'sync',
                    'job'        => $job,
                    'data'       => \json_decode($job->getRawBody(), true),
                ]
            );
        }
    }

    /**
     * Get a ErrorException instance.
     *
     * @param \ParseError|\Throwable|\TypeError $exception
     *
     * @return \ErrorException
     */
    private function getErrorException($exception): ErrorException
    {
        if ($exception instanceof ParseError) {
            $message  = 'Parse error: ' . $exception->getMessage();
            $severity = \E_PARSE;
        } elseif ($exception instanceof TypeError) {
            $message  = 'Type error: ' . $exception->getMessage();
            $severity = \E_RECOVERABLE_ERROR;
        } else {
            $message  = $exception->getMessage();
            $severity = \E_ERROR;
        }

        return new ErrorException(
            $message,
            $exception->getCode(),
            $severity,
            $exception->getFile(),
            $exception->getLine()
        );
    }
}
