<?php

namespace Infira\MeritAktiva;
class Customer extends \Infira\MeritAktiva\General
{
	public function setID($GUID)
	{
		$this->set("Id", $GUID);
	}
	
	public function setName($name)
	{
		$this->set("Name", $name);
	}
	
	public function setRegNo($no)
	{
		$this->set("RegNo", $no);
	}
	
	public function setISCompany($bool = FALSE)
	{
		$this->set("NotTDCustomer", !$bool);
	}
	
	public function setVatRegNo($no)
	{
		$this->set("VatRegNo", $no);
	}
	
	public function setCurrencyCode($code)
	{
		$this->set("CurrencyCode", $code);
	}
	
	public function setPaymentDeadLine($deadline)
	{
		$this->set("PaymentDeadLine", $deadline);
	}
	
	public function setOverDueCharge($charge)
	{
		$this->set("OverDueCharge", $charge);
	}
	
	public function setAddress($address)
	{
		$this->set("Address", $address);
	}
	
	public function setCity($city)
	{
		$this->set("City", $city);
	}
	
	public function setCountry($country)
	{
		$this->set("Country", $country);
	}

	public function setContact($contact)
	{
		$this->set("Contact", $contact);
	}

	public function setPostalCode($code)
	{
		$this->set("PostalCode", $code);
	}
	
	public function setCountryCode($no)
	{
		$this->set("CountryCode", $no);
	}
	
	public function setPhoneNo($no)
	{
		$this->set("PhoneNo", $no);
	}
	
	public function setPhoneNo2($no)
	{
		$this->set("PhoneNo2", $no);
	}
	
	public function setHomePage($no)
	{
		$this->set("HomePage", $no);
	}
	
	public function setEmail($email)
	{
		$this->set("Email", $email);
	}

    /**
     * @param Dimension[] $dimensions
     * @return void
     */
    public function setDimensions(array $dimensions)
    {
        $this->set("Dimensions", $dimensions);
    }
}
