<?php

use Nette\Object;
/**
 * Model Set sazeb operaci class
 */

class SetSazebO extends Model
{
	/**
	 *  @var string
	 * @table
	 */
	private $table = 'set_sazeb_o';
	

    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * Vrací obsah tabulky podle platnosti_od sestupně 
	 * @return record set
	 */
	
	public function show()
	{
		return $this->CONN->select('*')->from($this->table)->orderBy('platnost_od','DESC');

	}
	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		return $this->CONN->query("SELECT id, nazev [nazev], 
								platnost_od, 
								platnost_do 
								FROM set_sazeb_o
								WHERE id=$id");
	}
	
	/**
	 * Updates data in the table
	 * @params int, array
	 * @return mixed
	 */
	public function update($id, $data = array())
	{
		return $this->CONN->update($this->table, $data)->where('id=%i', $id)->execute();
	}
	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insert($data = array())
	{
		return $this->CONN->insert($this->table, $data)->execute(dibi::IDENTIFIER);
	}
	
	/**
	 * Deletes record in the table
	 * @param int
	 * @return mixed
	 */
	public function delete($id)
	{
		return $this->CONN->delete($this->table)->where('id=%i', $id)->execute();
	}

}


