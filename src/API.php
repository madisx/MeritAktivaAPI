<?php

namespace Infira\MeritAktiva;

use Error;

if (!function_exists('debug'))
{
	function debug()
	{
		$GLOBALS["debugIsActive"] = TRUE;
		$args                     = func_get_args();
		$html                     = "";

		if (count($args) == 1)
		{
			$html .= mertiApiDump($args[0]);
		}
		else
		{
			$html .= mertiApiDump($args);
		}
		$html = "<pre>$html</pre>";
		echo($html);
	}
}

function mertiApiDump($variable, $echo = FALSE)
{
    if (is_array($variable) or is_object($variable))
    {
        $html = print_r($variable, TRUE);
    }
    else
    {
        ob_start();
        var_dump($variable);
        $html = ob_get_clean();
    }
    if ($echo == TRUE)
    {
        exit($html);
    }

    return $html;
}

class API extends \Infira\MeritAktiva\General
{
    public const API_V1 = 'v1';
    public const API_V2 = 'v2';

	private $apiID           = "";
	private $apiKey          = "";
	private $lastRequestData = "";
	private $lastRequestUrl  = "";
	private $url             = "";
	private $debug           = FALSE;

	public function __construct($apiID, $apiKey, $country = 'ee', $vatPercent = 20)
	{
		$this->apiID  = $apiID;
		$this->apiKey = $apiKey;
		if ($country == 'ee')
		{
			$this->url = 'https://aktiva.merit.ee/api/';
		}
		elseif ($country == 'fi')
		{
			$this->url = 'https://aktiva.meritaktiva.fi/api/';
		}
		elseif ($country == 'pl')
		{
			$this->url = 'https://program.360ksiegowosc.pl/api/';
		}
		else
		{
			throw new Error("Unknown country");
		}

        if (!defined('MERIT_VAT_PERCENT')) {
            define("MERIT_VAT_PERCENT", $vatPercent);
        }
	}

	public function setDebug(bool $bool)
	{
		$this->debug = $bool;
	}


	public function getLastRequestData()
	{
		return $this->lastRequestData;
	}

	public function getLastRequestURL()
	{
		return $this->lastRequestUrl;
	}

    private function send($endPoint, $payload = null)
	{
		$timestamp = date("YmdHis");
		$urlParams = "";
		$json      = "";
		if ($payload)
		{
			if ($this->debug)
			{
				debug($payload);
			}
			$json = self::toUTF8(json_encode($payload));
		}

		$dataString            = $this->apiID . $timestamp . $json;
		$hash                  = hash_hmac("sha256", $dataString, $this->apiKey);
		$signature             = base64_encode($hash);
        $url                   = sprintf(
                                    '%s%s?ApiId=%s&timestamp=%s&signature=%s' . $urlParams,
                                    $this->url,
                                    $endPoint,
                                    $this->apiID,
                                    $timestamp,
                                    $signature
                                );
		$this->lastRequestUrl  = $url;
		$this->lastRequestData = $payload;

		$headers = [
			"Content-type: application/json",
		];

		if (isset($json)) {
			$headers[] = "Content-Length: " . strlen($json);
		}

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		if ($json)
		{
			curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		}
		$curlResponse = curl_exec($curl);
		if ($this->debug)
		{
			debug('$curlResponse', $curlResponse);
		}

		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($status != 200)
		{
			$error = "Error: call to URL $url <br>STATUS: $status<br>CURL_ERROR: " . curl_error($curl) . "<br> CURL_ERRNO: " . curl_errno($curl);
			$error .= '<br><br>API SAYS:' . mertiApiDump($this->jsonDecode($curlResponse, TRUE));

			return $error;
		}
		if (isset($curl))
		{
			curl_close($curl);
		}

		return $this->jsonDecode($curlResponse);
	}

	private function jsonDecode($json, $checkError = FALSE)
	{
		$json = stripslashes($json);
		if (substr($json, 0, 1) == '"' AND substr($json, -1) == '"')
		{
			$data = json_decode(substr($json, 1, -1));
		}
		else
		{
			$data = json_decode($json);
		}
		if (json_last_error() AND $checkError)
		{
			return $json;
		}

		return $data;
	}

	private static function toUTF8($string)
	{
		$string = trim($string);
		// return mb_convert_encoding($string, 'UTF-8');
		$encoding_list = 'UTF-8, ISO-8859-13, ISO-8859-1, ASCII, UTF-7';
		if (mb_detect_encoding($string, $encoding_list) == 'UTF-8')
		{
			return $string;
		}

		return mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string, $encoding_list));
	}

	//############ START OF endpoints

    /**
     * @param string $periodStart - string what is convertoed to time using strtotime()
     * @param string $periodEnd - string what is convertoed to time using strtotime()
     * @param string $apiVersion
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/reference-manual/sales-invoices/get-list-of-invoices/
     */
	public function getSalesInvoices($periodStart, $periodEnd, string $apiVersion = self::API_V1): APIResult
	{
		$payload = ["PeriodStart" => date("Ymd", strtotime($periodStart)), "PeriodEnd" => date("Ymd", strtotime($periodEnd))];

		return new APIResult($this->send("$apiVersion/getinvoices", $payload));
	}

    /**
     * Get sales invoice details
     *
     * @param string $GUID
     * @param string $apiVersion
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/reference-manual/sales-invoices/get-invoice-details/
     */
	public function getSalesInvoiceByID(string $GUID, string $apiVersion = self::API_V1): APIResult
	{

		return new APIResult($this->send("$apiVersion/getinvoice", ['id' => $GUID]));
	}

    /**
     * Delete sales invoice
     *
     * @param string $GUID
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/reference-manual/sales-invoices/delete-invoice/
     */
	public function deleteSalesInvoiceByID(string $GUID): APIResult
	{
		return new APIResult($this->send("v1/deleteinvoice", ['id' => $GUID]));
	}

    /**
     * Returns created invoice data
     *
     * @param SalesInvoice $Invoice
     * @param string $apiVersion
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/reference-manual/sales-invoices/create-sales-invoice/
     */
    public function createSalesInvoice(SalesInvoice $Invoice, string $apiVersion = self::API_V1): APIResult
    {
        return new APIResult($this->send("$apiVersion/sendinvoice", $Invoice->getData()));
    }
	
	/**
	 * Returns created invoice data
	 *
	 * @param SalesInvoice $Invoice
	 * @see https://api.merit.ee/reference-manual/sales-invoices/create-sales-invoice/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function createCreditSalesInvoice(SalesInvoice $Invoice, string $apiVersion = self::API_V1): APIResult
	{
		return new APIResult($this->send("$apiVersion/sendinvoice", $Invoice->getData()));
	}

    /**
     * @param string $periodStart - string what is convertoed to time using strtotime()
     * @param string $periodEnd - string what is convertoed to time using strtotime()
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/reference-manual/purchase-invoices/get-list-of-purchase-invoices/
     */
	public function getPurchaseInvoices($periodStart, $periodEnd, string $apiVersion = self::API_V1): APIResult
	{
		$payload = ["PeriodStart" => date("Ymd", strtotime($periodStart)), "PeriodEnd" => date("Ymd", strtotime($periodEnd))];
		
		return new APIResult($this->send("$apiVersion/getpurchorders", $payload));
	}

    /**
     * Get sales invoice details
     *
     * @param string $GUID
     * @param string $apiVersion
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/reference-manual/purchase-invoices/get-purchase-invoice-details/
     */
	public function getPurchaseInvoiceByID(string $GUID, string $apiVersion = self::API_V1): APIResult
	{
		return new APIResult($this->send("$apiVersion/getpurchorder", ['id' => $GUID]));
	}

    /**
     * Save purcahse invoice
     *
     * @param \Infira\MeritAktiva\PurchaseInvoice $Invoice
     * @param string $apiVersion
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/reference-manual/purchase-invoices/create-purchase-invoice/
     */
	public function createPurchaseInvoice(\Infira\MeritAktiva\PurchaseInvoice $Invoice, string $apiVersion = self::API_V1)
	{
		return new APIResult($this->send("$apiVersion/sendpurchinvoice", $Invoice->getData()));
	}

	/**
	 * Save invoice payment
	 *
	 * @param \Infira\MeritAktiva\Payment $Invoice
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function savePayment(\Infira\MeritAktiva\Payment $Invoice, string $apiVersion = self::API_V1)
	{
		return new APIResult($this->send("$apiVersion/sendpayment", $Invoice->getData()));
	}

    /**
     * Delete payment
     *
     * @param string $paymentId
     * @return \Infira\MeritAktiva\APIResult
     */
    public function deletePayment(string $paymentId)
    {
        return new APIResult($this->send("v1/deletepayment", ['Id' => $paymentId]));
    }

    /**
     * @see https://api.merit.ee/reference-manual/payments/list-of-payments/
     * @param $periodStart
     * @param $periodEnd
     * @return \Infira\MeritAktiva\APIResult
     */
	public function getPayments($periodStart, $periodEnd, string $apiVersion = self::API_V1): APIResult
	{
		$payload = ["PeriodStart" => date("Ymd", strtotime($periodStart)), "PeriodEnd" => date("Ymd", strtotime($periodEnd))];
		
		return new APIResult($this->send("$apiVersion/getpayments", $payload));
	}

	/**
	 * get Merit customers
	 *
	 * @param array $payload
	 * @see https://api.merit.ee/reference-manual/get-customer-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	private function getCustomersBy(array $payload): APIResult
	{
		return new APIResult($this->send("v1/getcustomers", $payload));
	}

	/**
	 * Get customer list
	 *
	 * @see https://api.merit.ee/reference-manual/get-customer-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getCustomers(string $apiVersion = self::API_V1): APIResult
	{
		return $this->getCustomersBy([], $apiVersion);
	}

	/**
	 * get merit vendor by ID
	 *
	 * @see https://api.merit.ee/reference-manual/get-customer-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getCustomersByID($GUID, string $apiVersion = self::API_V1)
	{
		return $this->getCustomersBy(["Id" => $this->validateGUID($GUID)], $apiVersion);
	}

	/**
	 * get merit vendor by RegNo
	 *
	 * @see https://api.merit.ee/reference-manual/get-customer-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getCustomersByRegNo($registryNumber, string $apiVersion = self::API_V1)
	{
		return $this->getCustomersBy(["RegNo" => $registryNumber], $apiVersion);
	}

	/**
	 * get merit vendor by VatRegNo
	 *
	 * @see https://api.merit.ee/reference-manual/get-customer-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getCustomersByVatRegNo($vatNumber, string $apiVersion = self::API_V1)
	{
		return $this->getCustomersBy(["VatRegNo" => $vatNumber], $apiVersion);
	}

	/**
	 * get merit vendor by Name
	 *
	 * @see https://api.merit.ee/reference-manual/get-customer-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getCustomersByName($name, string $apiVersion = self::API_V1)
	{
		return $this->getCustomersBy(["Name" => $name], $apiVersion);
	}

    /**
     * Returns created customer data
     *
     * @param Customer $Customer
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/connecting-robots/reference-manual/customers/create-customer/
     */
    public function createCustomer(Customer $Customer): APIResult
    {
        return new APIResult($this->send("v2/sendcustomer", $Customer->getData()));
    }

    /**
     * Returns created customer data
     *
     * @param Customer $Customer
     * @return \Infira\MeritAktiva\APIResult
     * @see https://api.merit.ee/connecting-robots/reference-manual/customers/update-customer/
     */
    public function updateCustomer(Customer $Customer): APIResult
    {
        return new APIResult($this->send("v1/updatecustomer", $Customer->getData()));
    }

	/**
	 * @see https://api.merit.ee/reference-manual/tax-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getTaxes()
	{
		return new APIResult($this->send("v1/gettaxes"));
	}

	/**
	 * @see https://api.merit.ee/connecting-robots/reference-manual/banks-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getBanks()
	{
		return new APIResult($this->send("v1/getbanks"));
	}

    /**
     * @see https://api.merit.ee/connecting-robots/reference-manual/accounts-list/
     * @return \Infira\MeritAktiva\APIResult
     */
    public function getAccounts()
    {
        return new APIResult($this->send("v1/getaccounts"));
    }

    /**
     * @see https://api.merit.ee/connecting-robots/reference-manual/dimensions/dimensionslist/
     * @return \Infira\MeritAktiva\APIResult
     */
    public function getDimensions()
    {
        return new APIResult($this->send("v2/getdimensions"));
    }

	/**
	 * Get tax details
	 *
	 * @param string $code
	 * @see https://api.merit.ee/reference-manual/tax-list/
	 * @return \stdClass|null
	 */
	public function getTaxDetails(string $code)
	{
		$Taxes = $this->getTaxes();
		if ($Taxes->isError())
		{
			$this->intError($Taxes->getError());
		}
		foreach ($Taxes->getRaw() as $Row)
		{
			if ($Row->Code == $code)
			{
				return $Row;
			}
		}

		return NULL;
	}

	/**
	 * @param array $payload
	 * @see https://api.merit.ee/reference-manual/get-vendor-list/
	 * @return \Infira\MeritAktiva\APIResult
	 */
	private function getVendorsBy(array $payload): APIResult
	{
		return new APIResult($this->send("v1/getvendors", $payload));
	}

	/**
	 * get merit vendors
	 *
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getVendors()
	{
		return $this->getVendorsBy([]);
	}

	/**
	 * get vendors by ID
	 *
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getVendorsByID($ID)
	{
		return $this->getVendorsBy(["Id" => $ID]);
	}

	/**
	 * get merit vendor by RegNo
	 *
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getVendorsByRegNo($no)
	{
		return $this->getVendorsBy(["RegNo" => $no]);
	}

	/**
	 * get merit vendor by VatRegNo
	 *
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getVendorsByVatRegNo($no)
	{
		return $this->getVendorsBy(["VatRegNo" => $no]);
	}

	/**
	 * get merit vendor by Name
	 *
	 * @return \Infira\MeritAktiva\APIResult
	 */
	public function getVendorsByName($name)
	{
		return $this->getVendorsBy(["Name" => $name]);
	}

	/**
	 * 
	 * @param string $guid 
	 * @return APIResult 
	 * @see https://api.merit.ee/connecting-robots/reference-manual/sales-invoices/create-sales-invoice/send-sales-invoice-by-einvoice/
	 */
	public function sendEInvoice(string $guid)
	{
		return new APIResult($this->send("v2/sendinvoiceaseinv", ["Id" => $this->validateGUID($guid)]));
	}
}
