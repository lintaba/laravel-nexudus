<?php

namespace Lintaba\LaravelNexudus;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Lintaba\LaravelNexudus\Exception\NexudusException;
use Psr\Log\LoggerInterface;

use function is_bool;

class NexudusConnector
{
    public const TAG = '[nexudus]';

    /**
     * @var string
     */
    public function __construct(
        protected ?LoggerInterface $logger,
        protected Client $client,
        protected ?string $user,
        protected ?string $password,
        protected ?string $wait,
        protected string $endpoint = 'https://spaces.nexudus.com/api'
    ) {
    }

    /**
     * @throws NexudusException
     */
    protected function fetch(string $method, string $endpoint, array $data = []): Collection
    {
        $tries = 0;
        $measureKey = self::TAG.$method.' '.$endpoint;
        app()->has('debugbar') && app('debugbar')->startMeasure($measureKey, $measureKey);

        while (true) {
            try {
                $res = $this->fetchActually($method, $endpoint, $data);
                app()->has('debugbar') && app('debugbar')->stopMeasure($measureKey);

                return $res;
            } catch (GuzzleException $e) {
                if ($e->getCode() == 409 && $tries < 5) {
                    $tries++;
                    sleep(2 * $tries);
                } elseif ($tries >= 5) {
                    app()->has('debugbar') && app('debugbar')->stopMeasure($measureKey);
                    throw new NexudusException('Tried '.$tries.' times, no success: '.$e->getMessage(), 0, $e);
                } else {
                    $this->logger?->warning('!> [Nexudus] Guzzle error '.$e->getMessage());
                    app()->has('debugbar') && app('debugbar')['messages']->warning('!> [Nexudus] Guzzle error '.$e->getMessage());
                    app()->has('debugbar') && app('debugbar')->stopMeasure($measureKey);
                    throw new NexudusException('Communication error: '.$e->getMessage(), 0, $e);
                }
            }
        }
    }

    /**
     * @throws GuzzleException
     * @throws NexudusException
     */
    protected function fetchActually(string $method, string $endpoint, array $data = []): Collection
    {
        if ($this->wait) {
            usleep($this->wait);
        }
        if ($method !== 'GET') {
            usleep(1000000 * 0.1);
        }

        if ($method === 'GET') {
            $endpoint .= '?'.\http_build_query($data);
            $data = null;
        }

        $options = [
            RequestOptions::AUTH => [$this->user, $this->password],
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Content' => 'application/json',
                'User-Agent' => 'php invoiceintegrator 1.0',
            ],
        ];

        if ($data) {
            $data = $this->recursiveMap(collect($data), static function ($value) {
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                return $value;
            })->all();
            $options[RequestOptions::JSON] = $data;
        }
        $options[RequestOptions::TIMEOUT] = 30;
        $options[RequestOptions::ALLOW_REDIRECTS] = false;
        $this->logger?->info('<< [Nexudus] '.$method.' '.$endpoint, ['data' => $data, 'options' => $options]);
        app()->has('debugbar') && app('debugbar')['messages']->debug('<< [Nexudus] '.$method.' '.$endpoint);
        $response = $this->client->request($method, $this->endpoint.$endpoint, $options);
        $responseBody = (string) $response->getBody();
        $this->logger?->debug(' > [Nexudus] '.$response->getStatusCode().', size='.$response->getBody()->getSize(), ['body' => substr($responseBody, 0, config('logBodySize', 1024))]);
        app()->has('debugbar') && app('debugbar')['messages']->debug(
            ' > [Nexudus] '.$response->getStatusCode().', size='.$response->getBody()->getSize(),
            ['body' => substr($responseBody, 0, config('logBodySize', 1024))]
        );

        if ($response->getStatusCode() !== 200) {
            $this->logger?->warning(
                '!> [Nexudus] Non-200 result:'.$response->getStatusCode(),
                ['body' => substr($responseBody, 0, config('logBodySize', 1024))]
            );
            app()->has('debugbar') && app('debugbar')['messages']->warning(
                '!> [Nexudus] Non-200 result:'.$response->getStatusCode(),
                ['body' => substr($responseBody, 0, config('logBodySize', 1024))]
            );
            throw new NexudusException(
                substr($responseBody, 0, config('logBodySize', 1024)),
                $response->getStatusCode()
            );
        }
        $result = json_decode($responseBody, true);

        if (is_array($result) && (($result['Errors'] ?? null) !== null || (head($result)['error'] ?? null) !== null)) {
            $this->logger?->warning(
                '!> [Nexudus] Error result:'.$response->getStatusCode(),
                ['result' => $result]
            );
            throw new NexudusException(
                $method.' '.$endpoint.PHP_EOL.json_encode($result).PHP_EOL.json_encode($data)
            );
        }

        return collect($result);
    }

    /**
     * @return Collection
     *
     * @throws NexudusException
     *
     * @see https://learn.nexudus.com/api/rest-api/billing/coworkerinvoice
     */
    public function getInvoices(array $params = [])
    {
        $endpoint = '/billing/coworkerinvoices';

        $result = $this->fetch('GET', $endpoint, $params);

        return $result;
    }

    /**
     * @return Collection
     *
     * @throws NexudusException
     *
     * @see https://learn.nexudus.com/api/rest-api/billing/coworkerinvoice
     */
    public function getInvoicesRaw(array $params = [])
    {
        $endpoint = '/billing/coworkerinvoices';

        $params += [
            'size' => 1000,
        ];

        $result = $this->fetch('GET', $endpoint, $params);

        return $result;
    }

    /**
     * @throws NexudusException
     */
    public function getInvoiceDetails(array $params, $invoiceId)
    {
        if (is_array($invoiceId) || $invoiceId instanceof Fluent) {
            $invoiceId = $invoiceId['Id'];
        }
        $endpoint = '/billing/coworkerinvoicelines';
        $params += [
            'size' => '1000',
            'CoworkerInvoiceLine_CoworkerInvoice' => $invoiceId,
        ];

        return $this->fetch('GET', $endpoint, $params);
    }

    /**
     * @see https://developers.nexudus.com/reference/get-coworker
     *
     * @throws NexudusException
     */
    public function getCoworker(array $params = [])
    {
        $endpoint = '/spaces/coworkers';
        $params += [
            'size' => '1000',
        ];

        return $this->fetch('GET', $endpoint, $params);
    }

    /**
     * @throws NexudusException
     */
    public function setCoworker(array $set = [])
    {
        $endpoint = '/spaces/coworkers';

        return $this->fetch('PUT', $endpoint, $set);
    }

    /**
     * @throws NexudusException
     */
    public function getUser(array $params = [])
    {
        $endpoint = '/sys/users';
        $params += [
            'size' => '1000',
        ];

        return $this->fetch('GET', $endpoint, $params);
    }

    /**
     * @throws NexudusException
     */
    public function getBusiness()
    {
        $endpoint = '/sys/businesses';

        return $this->fetch('GET', $endpoint);
    }

    /**
     * @throws NexudusException
     */
    public function webhooks(array $params = [])
    {
        $endpoint = '/sys/webhooks';
        $params += [
            'size' => '1000',
        ];

        return $this->fetch('GET', $endpoint, $params);
    }

    /**
     * @throws NexudusException
     */
    public function webhookDelete($hook)
    {
        if (is_array($hook)) {
            $hook = $hook['Id'];
        }

        $endpoint = '/sys/webhooks/'.$hook;

        return $this->fetch('DELETE', $endpoint);
    }

    /**
     * @throws NexudusException
     */
    public function webhookRegister(array $data)
    {
        $endpoint = '/sys/webhooks';

        return $this->fetch('POST', $endpoint, $data);
    }

    /**
     * @throws NexudusException
     */
    public function webhookUpdate(string $id, array $data)
    {
        $endpoint = '/sys/webhooks/'.$id;

        return $this->fetch('PUT', $endpoint, $data);
    }

    public function enum(string $name): array
    {
        return cache()->remember(/**
         * @throws NexudusException
         */ 'nexudusEnumList'.$name, now()->addDay(), function () use ($name) {
            $endpoint = '/utils/enums';
            $params = ['name' => $name];
            $options = $this->fetch('GET', $endpoint, $params);

            return collect($options)->pluck('Id', 'Name')->all();
        });
    }

    /**
     * @throws NexudusException
     * @throws Exception
     */
    public function command(string $type, array $data)
    {
        switch ($type) {
            case 'innvoice':
                $endpoint = '/billing/coworkerinvoices/runcommand';
                break;
            default:
                throw new Exception('unknown command type: '.$type);
        }
        $result = $this->fetch('POST', $endpoint, $data);
        if (! ($result['WasSuccessful'] ?? true) && $result['Message'] ?? null) {
            throw new NexudusException($result['Message']);
        }

        return $result;
    }

    /**
     * @throws NexudusException
     */
    public function updateInvoice(array $set)
    {
        $endpoint = '/billing/coworkerinvoices';

        return $this->fetch('PUT', $endpoint, $set);
    }

    /**
     * This method applies a recursive map operation over a Collection (or an Array).
     *
     * It uses the provided callable function $fn and applies it to every element in the collection.
     * If an element of the collection is an array or a Collection itself,
     * it applies this method recursively before applying the function.
     *
     * The method is useful when you need to apply a transformation to all elements
     * in a Collection or Array, regardless of their depth level.
     *
     * @param  Collection  $collection  The collection to map over
     * @param  callable  $fn  The function to apply to each element.
     * @return Collection The transformed collection
     */
    private function recursiveMap(Collection $collection, callable $fn)
    {
        return $collection->map(static function ($item, $key) use ($fn) {
            if (is_array($item)) {
                return $this->recursiveMap(collect($item), $fn)->toArray();
            }

            if ($item instanceof Collection) {
                return $item->recursiveMap($fn);
            }

            return $fn($item);
        });
    }
}
