<?php

namespace Infira\MeritAktiva;
class APIResult
{
	private $res;
	
	public function __construct($res)
	{
		$this->res = $res;
	}
	
	public function isError()
	{
		// sendinvoicebyemail method returns NULL on successful request
	        // TODO I think its time to start checking status code as well
	        if (is_null($this->res)) {
	            return false;
	        }

		return !(is_object($this->res) || is_array($this->res));
	}

    /**
     * @return array|object|string
     */
	public function getRaw()
	{
		return $this->res;
	}
	
	public function getError()
	{
		return $this->res;
	}
}
