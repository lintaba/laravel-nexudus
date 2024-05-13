<?php

declare(strict_types=1);

namespace Lintaba\LaravelNexudus\Data;

use Illuminate\Support\Fluent;

/**
 * @property mixed $CoworkerId
 * @property string $CoworkerFullName
 * @property mixed|null $CoworkerRegularPaymentContractNumber
 * @property mixed $CoworkerRegularPaymentProvider
 * @property mixed|null $CoworkerCardNumber
 * @property mixed|null $CoworkerGoCardlessContractNumber
 * @property bool $CoworkerEnableGoCardlessPayments
 * @property string $CoworkerBillingEmail
 * @property bool $CoworkerNotifyOnNewInvoice
 * @property bool $CoworkerNotifyOnNewPayment
 * @property bool $CoworkerNotifyOnFailedPayment
 * @property bool $CoworkerDoNotProcessInvoicesAutomatically
 * @property mixed $BusinessId
 * @property string $BusinessName
 * @property string $InvoiceNumber
 * @property string $PaymentReference
 * @property string $BillToName
 * @property string $BillToAddress
 * @property string $BillToCity
 * @property string $BillToPostCode
 * @property mixed|null $BillToPhone
 * @property mixed|null $BillToFax
 * @property mixed $BillToCountryId
 * @property string $BillToCountryName
 * @property string $BillToCountryTwoDigitsCode
 * @property mixed|null $BillToBankAccount
 * @property string $BillToTaxIDNumber
 * @property mixed|null $PurchaseOrder
 * @property string $Description
 *                               package) #hónap, 1db melegital kiegészítő | 1pcs Hot beverage #db"
 * @property float $DiscountAmount
 * @property string $DueDate
 * @property string $InvoiceFromDate
 * @property string $InvoiceToDate
 * @property float $TotalAmount
 * @property float $PaidAmount
 * @property mixed $CurrencyId
 * @property string $CurrencyCode
 * @property mixed $TaxAmount
 * @property bool $Draft
 * @property bool $Paid
 * @property bool $Sent
 * @property mixed|null $SentOn
 * @property mixed|null $PaidOn
 * @property bool $Refunded
 * @property bool $XeroInvoiceTransfered
 * @property bool $XeroPaymentTransfered
 * @property bool $MoloniInvoiceTransferred
 * @property bool $MoloniPaymentTransferred
 * @property mixed|null $RefundedOn
 * @property bool $CreditNote
 * @property mixed|null $OriginalInvoiceGuid
 * @property string $ContractGuid
 * @property string $CustomData
 * @property float $ReceivedAmount
 * @property float $CreditedAmount
 * @property float $RefundedAmount
 * @property mixed|null $UniqueSquareIdentifier
 * @property float $TotalAmountFormated
 * @property string $DueDateFormated
 * @property mixed|null $InvoiceLines
 * @property mixed $IsDue
 * @property float $DueAmount
 * @property mixed $Id
 * @property string $UpdatedOn
 * @property string $CreatedOn
 * @property string $UniqueId
 * @property string $UpdatedBy
 * @property bool $IsNew
 * @property mixed|null $SystemId
 * @property string $ToStringText
 * @property mixed|null $LocalizationDetails
 */
class NexudusInvoiceDto extends Fluent
{
    public function __construct($attributes = [])
    {
        if ($attributes instanceof Fluent) {
            report(new \Exception('its already fluent'));
        }
        if (isset($attributes['attributes'])) {
            report(new \Exception('it has an attributes field only.'));
        }
        parent::__construct($attributes);
    }
}
