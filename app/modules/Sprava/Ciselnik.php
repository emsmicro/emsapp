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

	public function getColumns($table)
	{
		return $this->CONN->query("SELECT column_name [name], data_type [type] FROM information_schema.columns"
				. " WHERE table_name like '%$table%'");
	}
	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id, $table)
	{
		return $this->CONN->select('*')->from($table)->where('id=%i', $id);
	}
	
	/**
	 * Updates data in the table
	 * @params int, array
	 * @return mixed
	 */
	public function update($id, $table, $data = array())
	{
		return $this->CONN->update($table, $data)->where('id=%i', $id)->execute();
	}
	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insert($table, $data = array())
	{
		return $this->CONN->insert($table, $data)->execute(dibi::IDENTIFIER);
	}
	
	/**
	 * Deletes record in the table
	 * @param int
	 * @return mixed
	 */
	public function delete($id, $table)
	{
		return $this->CONN->delete($table)->where('id=%i', $id)->execute();
	}	
	
}


