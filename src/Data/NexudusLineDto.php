<?php

namespace Lintaba\LaravelNexudus\Data;

use DateTime;
use Illuminate\Support\Fluent;

/**
 * Class NexudusLineDto
 *
 * @property mixed $CoworkerInvoiceId
 * @property string $Description
 * @property string $TaxCategoryName
 * @property mixed $Quantity
 * @property mixed $SubTotal
 * @property float $TaxAmount
 * @property mixed $TaxRate
 * @property mixed $CoworkerContractUniqueId
 * @property mixed $ContractDepositUniqueId
 * @property mixed $BookingUniqueId
 * @property string $CoworkerExtraServiceUniqueId
 * @property mixed $CoworkerTimePassUniqueId
 * @property mixed $CoworkerChargeUniqueId
 * @property mixed $CoworkerProductUniqueId
 * @property mixed $EventAttendeeUniqueId
 * @property mixed $RefundedAmount
 * @property bool $Refunded
 * @property mixed $RefundedOn
 * @property DateTime $SaleDate
 * @property mixed $DiscountCode
 * @property mixed $DiscountAmount
 * @property string $CoworkerExtraServiceName
 * @property mixed $CoworkerTimePassName
 * @property mixed $CoworkerProductName
 * @property mixed $EventAttendeeProductName
 * @property mixed $TariffName
 * @property mixed $FinancialAccountCode
 * @property mixed $FinancialAccountName
 * @property mixed $Position
 * @property mixed $UnitPrice
 * @property mixed $Id
 * @property DateTime $UpdatedOn
 * @property DateTime $CreatedOn
 * @property string $UniqueId
 * @property string $UpdatedBy
 * @property bool $IsNew
 * @property mixed $SystemId
 * @property string $ToStringText
 * @property mixed $LocalizationDetails
 */
class NexudusLineDto extends Fluent
{
}
