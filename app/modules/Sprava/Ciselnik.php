<?php

use Nette\Object;
/**
 * Model Ciselnik class
 */

class Ciselnik extends Model
{


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * 	Vrací obsah tabulky podle parametru
	 * @param string
	 * @return record set
	 */
	public function showCis($table)
	{
		return $this->CONN->select('*')->from($table);
	}

}


