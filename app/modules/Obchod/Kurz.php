<?php

use Nette\Object;

/**
 * Model Kurz class
 */
class Kurz extends Model
{
	/** @var string
	 * @table
	 */
	private $table = 'kurzy';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }

	/**
	 * 	Vrací vybrané atributy tabulky
	 * @return record set
	 */
	public function show()
	{
		return $this->CONN->dataSource("SELECT k.*, m.zkratka [mzkratka] FROM kurzy k
									LEFT JOIN meny m ON k.id_meny=m.id
								");
	}
	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		return $this->CONN->query("SELECT 	k.id,m.zkratka [mzkratka], m.nazev [mnazev],
								k.id_meny [k_id_meny], 
								ROUND(k.kurz_nakupni,5) [k_kurz_nakupni],
								ROUND(k.kurz_prodejni,5) [k_kurz_prodejni],
								k.platnost_od [k_platnost_od],
								k.platnost_do [k_platnost_do]
							FROM kurzy k
							LEFT JOIN meny m ON k.id_meny=m.id
							WHERE k.id=$id"
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

}


