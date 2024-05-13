<?php

namespace Lintaba\LaravelNexudus;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class Nexudus
{
    /**
     * NexudusService constructor.
     */
    public function __construct(protected NexudusConnector $connector, protected array $config)
    {
    }

    public static function invoiceLink($InvoiceId): string
    {
        return 'https://platform.nexudus.com/operations/customers/0/invoices/'.$InvoiceId;
    }

    public static function customerLink($customerLink): string
    {
        return 'https://platform.nexudus.com/operations/customers/'.$customerLink;
    }

    public function customerName($coworkerId): ?string
    {
        return $this->getCoworker($coworkerId)?->FullName;
    }

    /**
     * @throws Exception\NexudusException
     *
     * @see  NexudusConnector::getInvoices
     * @link NexudusConnector::getInvoices
     * @link https://developers.nexudus.com/reference-link/search-coworkerinvoice
     */
    public function fetchInvoices(array $params = []): Collection
    {
        $since = max(Carbon::make($this->config['syncEpoch']), now()->subDays($this->config['syncDays']));

        $params += [
            'size' => 1000,
            'from_CoworkerInvoice_CreatedOn' => $since->format(Carbon::ATOM),
        ];
        $debugUid = (string) $this->config['debug_nexudus_user'];
        if ($debugUid && strpos($debugUid, '~') !== 0 && isset($params['CoworkerInvoice_Coworker'])) {
            $params['CoworkerInvoice_Coworker'] = $debugUid;
        }
        $result = $this->getAllPages([$this->connector, 'getInvoices'], $params);

        if ($debugUid) {
            $exclude = strpos($debugUid, '~') === 0;
            $debugUid = trim($debugUid, '~ ');
            $result = array_filter($result ?? [], fn ($row) => ($debugUid == $row['CoworkerId']) xor $exclude);
        }

        return $result;
    }

    /**
     * @throws Exception\NexudusException
     *
     * @see  NexudusConnector::fetchLines
     * @link NexudusConnector::fetchLines
     */
    public function fetchInvoiceLines($invoice): Collection
    {
        return $this->getAllPages([$this->connector, 'getInvoiceDetails'], [], $invoice);
    }

    /**
     * @see  NexudusConnector::getBusinesses
     * @link NexudusConnector::getBusinesses
     */
    public function getBusinesses(): Collection
    {
        return $this->getAllPages([$this->connector, 'getBusiness']);
    }

    /**
     * @return Collection
     *
     * @throws Exception
     *
     * @see  NexudusConnector::command
     * @link NexudusConnector::command
     */
    public function command(string $type, array $params)
    {
        return $this->connector->command($type, $params);
    }

    /**
     * @throws Exception\NexudusException
     *
     * @see  NexudusConnector::listWebhooks
     * @link NexudusConnector::listWebhooks
     */
    public function listWebhooks(): Collection
    {
        return $this->getAllPages([$this->connector, 'webhooks']);
    }

    /**
     * @see  NexudusConnector::checkWebhooks
     * @link NexudusConnector::checkWebhooks
     */
    public function checkWebhooks(int $min, string $pattern = '[II]'): bool
    {
        $hooks = $this->listWebhooks();
        $own = $hooks->filter(static fn (array $hook): bool => Str::startsWith($hook['Name'], $pattern));
        $inactive = $own->filter(static fn (array $hook): bool => ! $hook['Active']);

        return $inactive->isEmpty() && $own->count() >= $min;
    }

    /**
     * @param  mixed  ...$extraMethodParams
     *
     * @throws Exception\NexudusException
     */
    protected function getAllPages(callable $fn, array $data = [], ...$extraMethodParams): Collection
    {
        return LazyCollection::make(
            static function () use ($fn, $data, $extraMethodParams) {
                $data['page'] = 1;
                do {
                    $result = $fn($data, ...$extraMethodParams);
                    /** @noinspection YieldFromCanBeUsedInspection */
                    foreach ($result['Records'] ?? [] as $record) {
                        yield $record;
                    }
                    $data['page']++;
                } while ($result['HasNextPage'] ?? false);
            }
        )->collect();
    }

    /**
     * @see  NexudusConnector::unsubscribeWebhooks
     * @link NexudusConnector::unsubscribeWebhooks
     */
    public function unsubscribeWebhooks(string $pattern = '[II]'): int
    {
        $removed = 0;
        $hooks = $this->listWebhooks();
        foreach ($hooks as $hook) {
            if (Str::startsWith($hook['Name'], $pattern)) {
                $this->connector->webhookDelete($hook);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @see  NexudusConnector::activateWebhooks
     * @link NexudusConnector::activateWebhooks
     */
    public function activateWebhooks(string $pattern = '[II]'): int
    {
        $activated = 0;
        $hooks = $this->listWebhooks();
        foreach ($hooks as $hook) {
            if (Str::startsWith($hook['Name'], $pattern)) {
                $this->connector->webhookUpdate($hook['Id'], ['Active' => true]);
                $activated++;
            }
        }

        return $activated;
    }

    /**
     * @see  NexudusConnector::subscribeWebhooks
     * @link NexudusConnector::subscribeWebhooks
     */
    public function subscribeWebhooks(array $hooks, int $nexudusBusinessId, string $pattern = '[II]', string $route = 'webhook.invoke'): int
    {
        $created = 0;
        $hookEnumList = $this->connector->enum('eWebhookAction');
        foreach ($hooks as $hookName) {
            $name = $pattern.' '.Str::snake($hookName, ' ').' '.now()->format('m-d');
            $params = [
                'BusinessId' => $nexudusBusinessId,
                'Name' => $name,
                'Action' => $hookEnumList[$hookName],
                'Description' => "InvoiceConnector's autogenerated hook for ".$hookName.'.',
                'URL' => URL::signedRoute($route, [$hookName]),
                'Active' => true,
            ];
            $this->connector->webhookRegister($params);
            $created++;
        }

        return $created;
    }

    /**
     * @throws Exception\NexudusException
     *
     * @see  NexudusConnector::getCoworker
     * @link NexudusConnector::getCoworker
     */
    public function getCoworker(int $CoworkerId): ?Data\NexudusCoworkerDto
    {
        return $this->getCoworkerByField('Id', $CoworkerId);
    }

    /**
     * @param  int  $CoworkerId
     *
     * @throws Exception\NexudusException
     *
     * @see  NexudusConnector::getCoworker
     * @link NexudusConnector::getCoworker
     */
    public function getCoworkerByField(string $key, $value): ?Data\NexudusCoworkerDto
    {
        $result = $this->connector->getCoworker(['Coworker_'.$key => $value, 'size' => 1]);
        if (! $result || empty($result)) {
            return null;
        }
        $result = data_get($result, 'Records.0');
        if (! $result) {
            return null;
        }

        return new Data\NexudusCoworkerDto($result);
    }

    public function getInvoiceByField(string $key, $value): ?Data\NexudusInvoiceDto
    {
        $result = $this->connector->getInvoices(
            [
                $key => $value,
                'size' => 1,
                'from_CoworkerInvoice_CreatedOn' => null,

            ]
        );
        if (! $result || empty($result)) {
            return null;
        }
        $result = data_get($result, 'Records.0');
        if (! $result) {
            return null;
        }

        return new Data\NexudusInvoiceDto($result);
    }

    /**
     * @return array|Collection
     *
     * @see https://learn.nexudus.com/api/rest-api/billing/coworkerinvoice#update
     */
    /**
     * @see  NexudusConnector::updateInvoice
     * @link NexudusConnector::updateInvoice
     */
    public function updateInvoice(array $item, array $original): void
    {
        $original = array_filter($original, function ($e) {
            return $e !== null;
        });
        $payload = $item + $original;

        if ($payload != $original) {
            $payload = array_filter($payload, function ($e) {
                return $e !== null;
            });

            $this->connector->updateInvoice($payload);
        }
    }

    public function transformPaymode($paymode): ?int
    {
        switch ($paymode) {
            case 'card':
            case '15':
                //CreditCard
                return 15;
            case 'átutalás':
            case '996':
                //Manual (Bank Transfer)
                return 996;
            case 'készpénz':
            case '997':
                //Manual (Cash)
                return 997;
            case 'csekk':
            case '998':
                //Manual (Check)
                return 998;
            case 'egészségpénztár':
            case '992':
                //Manual (Credit Card  - Terminal 1)
                return 992;
            case 'postautalvány':
            case '993':
                //Manual (Credit Card - Terminal 2)
                return 993;
            case 'bankkártya':
            case '991':
                //Manual (Credit Card)
                return 991;
            case 'utánvét':
            case '994':
                //Manual (Credit Note)
                return 994;
            case '989':
                //Manual (Direct Debit)
                return 989;
            case 'kompenzáció':
            case '995':
                //Manual (Gift Card)
                return 995;
            case '999':
                //Manual (Other Method)
            default:
                return 999;
        }
    }

    public function markPaid(Data\NexudusInvoiceDto $nexudusDto, int $type)
    {
        return $this->command('innvoice', [
            'Key' => 'PAY_COWORKER_INVOICE_MANUAL',
            'Parameters' => [
                [
                    'Name' => 'Payment Method',
                    'Type' => 'ePaymentProvider',
                    'Value' => $type,
                ],
            ],
            'Ids' => [$nexudusDto->Id ?? $nexudusDto->attributes['Id'] ?? null],
        ]);
    }

    public function getUser(int $userId)
    {
        return $this->getAllPages([$this->connector, 'getUser'], ['Id' => $userId])->first();
    }
}
