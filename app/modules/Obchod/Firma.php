<?php

use Nette\Object;

/*
 * Model Firma class
 */

class Firma extends Model
{
	/* @var string
	 * @table
	 */
	private $table = 'firmy';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	/*	Vrací vybrané sloupce 
	 * @return record set
	 */
	public function show()
	{
		$sql_cmd = "
				SELECT f.*, d.zkratka [dzkratka], a.ulice, a.cp, o.nazev [obec], o.psc, k.nazev [kraj], s.nazev [stat], s.zkratka [zstat]
				FROM firmy f
					LEFT JOIN druhy_firem d ON f.id_druhy_firem=d.id
					LEFT JOIN adresy a 		ON f.id_adresy=a.id
					LEFT JOIN obce o 		ON a.id_obce=o.id
					LEFT JOIN kraje k 		ON a.id_kraje=k.id
					LEFT JOIN staty s 		ON a.id_staty=s.id
				";
		if($this->filter<>''){
			$sql_cmd = $sql_cmd . "	WHERE f.zkratka + f.nazev + a.ulice + o.nazev + s.zkratka + s.nazev LIKE '%$this->filter%'";
		}
		return $this->CONN->dataSource($sql_cmd);
	}
	
	/*
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		return $this->CONN->dataSource("
				SELECT 	f.*,
						d.zkratka [dzkratka], a.ulice [a_ulice], a.cp [a_cp], o.id [a_id_obce],
						o.nazev [m_obec], o.psc [m_psc],
						k.nazev [m_kraj], k.zkratka [m_zkraj],
						s.nazev [m_stat], s.zkratka [m_zstat],
						a.id_kraje [a_id_kraje], a.id_staty [a_id_staty]
				FROM firmy f
					LEFT JOIN druhy_firem d ON f.id_druhy_firem=d.id
					LEFT JOIN adresy a 		ON f.id_adresy=a.id
					LEFT JOIN obce o 		ON a.id_obce=o.id
					LEFT JOIN kraje k 		ON a.id_kraje=k.id
					LEFT JOIN staty s 		ON a.id_staty=s.id
				WHERE f.id = $id"
				);
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


