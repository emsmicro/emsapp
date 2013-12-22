<?php

use Nette\Object;
/*
 * Model Kontakt class
 */
class Kontakt extends Model
{
	/* @var string
	 * @table
	 */
	private $table = 'kontakty';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/*	Vrací atributy pro konkrétní osobu 
	 * @param int
	 * @return record set
	 */
	public function showO($ido=0)
	{
		return $this->CONN->dataSource('SELECT k.*, o.prijmeni [osoba], tk.nazev [ktyp]
								FROM kontakty k
								LEFT JOIN osoby o ON k.id_osoby=o.id
								LEFT JOIN typy_kontaktu tk ON k.id_typy_kontaktu=tk.id
                                WHERE k.id_osoby='.$ido
								);
	}
	/*	Vrací atributy pro konkrétní firmu
	 * @param int
	 * @return record set
	 */
    public function showF($idf=0)
	{
		return $this->CONN->dataSource('SELECT k.*, f.nazev [firma], tk.nazev [ktyp]
								FROM kontakty k
								LEFT JOIN firmy f ON k.id_firmy=f.id
								LEFT JOIN typy_kontaktu tk ON k.id_typy_kontaktu=tk.id
                                WHERE k.id_firmy='.$idf
								);
	}
	/*
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		return $this->CONN->select('*')->from($this->table)->where('id=%i', $id);
	}
	/*
	 * Updates data in the table
	 * @params int, array
	 * @return mixed
	 */
	public function update($id, $data = array())
	{
		return $this->CONN->update($this->table, $data)->where('id=%i', $id)->execute();
	}
	/*
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insert($data = array())
	{
		return $this->CONN->insert($this->table, $data)->execute(dibi::IDENTIFIER);
	}
	/*
	 * Deletes record in the table
	 * @param int
	 * @return mixed
	 */
	public function delete($id)
	{
		return $this->CONN->delete($this->table)->where('id=%i', $id)->execute();
	}


}


