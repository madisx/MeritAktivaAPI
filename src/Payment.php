<?php

namespace Infira\MeritAktiva;

/**
 * https://api.merit.ee/connecting-robots/reference-manual/payments/create-payment/
 */
class Payment extends \Infira\MeritAktiva\General
{
	public function __construct()
	{
		$this->setMandatoryField('CustomerName');
		$this->setMandatoryField('InvoiceNo');
		$this->setMandatoryField('Amount');
	}

	public function setBankId(string $bankId)
	{
		$this->set('BankId', $bankId);
	}

	public function setIBAN(string $IBAN)
	{
		$this->set('IBAN', $IBAN);
	}

	public function setCustomerName(string $CustomerName)
	{
		$this->set('CustomerName', $CustomerName);
	}

	public function setInvoiceNo(string $InvoiceNo)
	{
		$this->set('InvoiceNo', $InvoiceNo);
	}

	public function setPaymentDate(string $date)
	{
        $this->set("PaymentDate", $this->convertDate($date));
	}

	public function setRefNo(int $refNo)
	{
		$this->set('RefNo', $refNo);
	}

	public function setAmount(float $Amount)
	{
		$this->set('Amount', $Amount);
	}

}
