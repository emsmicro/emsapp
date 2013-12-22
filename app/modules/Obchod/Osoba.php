<?php

use Nette\Object;
/*
 * Model Osoba class
 */
class Osoba extends Model
{
	/**
	 * @var string
	 * @table
	 */
	private $table = 'osoby';
	/** @var Nette\Database\Connection */ 
	public $connection;


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * 	Vrací vybrané atributy 
	 * @return record set
	 */
	public function show()
	{
		return $this->CONN->dataSource('SELECT o.*,f.nazev [ofirma],os.nazev [oosloveni], f.id [idf] FROM osoby o
                                LEFT JOIN firmy f ON o.id_firmy=f.id
                                LEFT JOIN osloveni os ON o.id_osloveni=os.id');
	}
	
	/**
	 * 	Vrací vybrané atributy pro konkrétní firmu
	 * @return record set
	 */
	public function showPeople($id_firmy)
	{
		return $this->CONN->query("SELECT o.*,f.nazev [ofirma],os.nazev [oosloveni], f.id [idf], 
									COALESCE(k.hodnota,'žádný') [kontakt], k.id_typy_kontaktu [idk]
								FROM osoby o
                                LEFT JOIN firmy f ON o.id_firmy=f.id
                                LEFT JOIN osloveni os ON o.id_osloveni=os.id
								LEFT JOIN kontakty k ON o.id=k.id_osoby
                                WHERE o.id_firmy = $id_firmy
								ORDER BY o.prijmeni, o.jmeno, k.id_typy_kontaktu
								");
	}

	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		return $this->CONN->dataSource('SELECT o.*,f.nazev [ofirma],os.nazev [osloveni] FROM osoby o
                                LEFT JOIN firmy f ON o.id_firmy=f.id
                                LEFT JOIN osloveni os ON o.id_osloveni=os.id
                                WHERE o.id='.$id
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


