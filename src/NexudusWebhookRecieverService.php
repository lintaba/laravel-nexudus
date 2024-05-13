<?php

namespace Lintaba\LaravelNexudus;

class NexudusWebhookRecieverService
{
    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * @var InnvoiceService
     */
    private $innvoiceService;

    /**
     * NexudusService constructor.
     *
     * @param  NexudusConnector  $nexudusConnector
     */
    public function __construct(SyncService $syncService, InnvoiceService $innvoiceService)
    {
        $this->syncService = $syncService;
        $this->innvoiceService = $innvoiceService;
    }

    public function handle($type, array $input)
    {
        if (isset($input[0])) {
            $input = $input[0];
        }
        switch ($type) {
            case 'CoworkerInvoiceCreateFirst':
                //                $this->handleCoworkerInvoiceCreateFirst($input);
                return;
            case 'CoworkerInvoiceCreate':
                sleep(option('minutesWaitBeforeInvoice', 30) * 60);
                $this->handleCoworkerInvoiceCreate($input);

                return;
            case 'CoworkerInvoiceDelete':
                //                $this->handleCoworkerInvoiceDelete($input);
                return;
            case 'CoworkerInvoiceUpdate':
                //                $this->handleCoworkerInvoiceUpdate($input);
                return;
            case 'CoworkerInvoiceRefund':
                //                $this->handleCoworkerInvoiceRefund($input);
                return;
            case 'CoworkerInvoiceCreditNote':
                //                $this->handleCoworkerInvoiceCreditNote($input);
                return;
            case 'CoworkerInvoicePaid':
                $this->handleCoworkerInvoicePaid($input);

                return;
            default:
                throw new \Exception('unknown type for webhookhandling: '.$type);
        }
    }

    public function handleCoworkerInvoiceCreateFirst(array $options): void
    {

    }

    public function handleCoworkerInvoiceCreate(array $options): array
    {
        $invoice = (array) $options;

        return $this->syncService->handleItem($invoice, collect(), SyncService::STATUS_MISSING);

    }

    public function handleCoworkerInvoiceDelete(array $options): void
    {

    }

    public function handleCoworkerInvoiceUpdate(array $options): void
    {

    }

    public function handleCoworkerInvoiceRefund(array $options): void
    {

    }

    public function handleCoworkerInvoiceCreditNote(array $options): void
    {

    }

    public function handleCoworkerInvoicePaid(array $options): ?array
    {
        $nexudusInnvoice = (array) $options;
        $innvoiceItem = $this->innvoiceService->fetchInvoiceByNexudusEntry($nexudusInnvoice);
        if ($innvoiceItem) {
            return $this->syncService->handleItem(
                $nexudusInnvoice,
                collect([$innvoiceItem]),
                SyncService::STATUS_NEXUDUS_PAID
            );
        } else {
            logger()->error('[hook InvoicePaid]: No invoice found for item: ', ['nexudusInnvoice' => $nexudusInnvoice]);

            return [
                'success' => false,
                'error' => 'No invoice found for item.',
            ];
        }
    }
}
