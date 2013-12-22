<?php

use Nette\Object;
/**
 * Model Pocet class
 */
class Pocet extends Model
{
	/**
	 *  @var string
	 *  @table
	 */
	private $table = 'pocty';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * 	Vrací atributy pro konkrétní osobu 
	 * @param int
	 * @return record set
	 */
	public function show($idp=0)
	{
		return $this->CONN->select('*')->from($this->table)->where('id_produkty=%i', $idp);
	}

	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		return $this->CONN->select('*')->from($this->table)->where('id=%i', $id);
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


