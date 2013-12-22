<?php

use Nette\Object;
/**
 * Model Set sazeb class
 */

class SetSazeb extends Model
{
	/**
	 *  @var string
	 * @table
	 */
	private $table = 'set_sazeb';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * 	Vrací obsah tabulky 
	 *  @return record set
	 */
	public function show()
	{
		//return $this->CONN->select('*')->from($this->table)->orderBy('platnost_od','DESC');
		return $this->CONN->query("SELECT ss.id, ss.nazev [nazev], 
								platnost_od,
								platnost_do,
								ka.zkratka [kzkratka], ka.nazev [knazev], ka.popis
								FROM set_sazeb ss
								LEFT JOIN kalkulace ka ON ss.kalkulace = ka.id
								ORDER BY platnost_od DESC"
								);
	}
	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */	
	public function find($id)
	{
		return $this->CONN->query("SELECT ss.id, ss.nazev [nazev], 
								platnost_od,
								platnost_do,
								kalkulace,
								ka.zkratka [kzkratka], ka.nazev [knazev], ka.popis
								FROM set_sazeb ss
								LEFT JOIN kalkulace ka ON ss.kalkulace = ka.id
								WHERE ss.id=$id"
								);
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

	/**
	 * Vrací všechny atributy tabulky Kalkulace
	 * @param int
	 * @return record set
	 */
	public function showKalk()
	{
		return $this->CONN->select('*')->from('kalkulace');
	}
	
	/**
	 * Vrací seznam kalkulačních vzorců
	 * @return type 
	 */
	public function getKalkul() {
		$kalk = $this->CONN->fetchPairs("SELECT id, zkratka+': '+nazev [nazev] from kalkulace");
		return $kalk;
	}	

	/**
	 * Vrací data pro konkrétní záznam Kalkulace
	 * @param int
	 * @return record set
	 */	
	public function findKalk($id)
	{
		return $this->CONN->select('*')->from('kalkulace')->where('id=%i', $id);
	}
	
	/**
	 * Updates data in the table Kalkulace
	 * @params int, array
	 * @return mixed
	 */
	public function updateKalk($id, $data = array())
	{
		return $this->CONN->update('kalkulace', $data)->where('id=%i', $id)->execute();
	}
	
	/**
	 * Inserts data to the table Kalkulace
	 * @param array
	 * @return Identifier
	 */
	public function insertKalk($data = array())
	{
		return $this->CONN->insert('kalkulace', $data)->execute(dibi::IDENTIFIER);
	}
	
	/**
	 * Deletes record in the table Kalkulace
	 * @param int
	 * @return mixed
	 */
	public function deleteKalk($id)
	{
		return $this->CONN->delete('kalkulace')->where('id=%i', $id)->execute();
	}
	
	
	
	
}


