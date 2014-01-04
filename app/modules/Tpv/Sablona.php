<?php

use Nette\Object;
/**
 * Model Set sazeb operaci class
 */

class Sablona extends Model
{
	/**
	 *  @var string
	 * @table
	 */
	private $table = 'tp_sablony';
	

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
		return $this->CONN->select('*')->from($this->table);

	}
	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
//		return $this->CONN->select('*')->from($this->table)->where('id=%i', $id);
		return $this->CONN->query("SELECT sa.*, 
								(SELECT MAX(CONVERT(int, poradi)) FROM tp_sablony_typy
												WHERE id_tp_sablony = $id) [mporadi]
								FROM tp_sablony sa
								WHERE sa.id = $id
								");
	}

	
	public function findSablTyp($id)
	{
		return $this->CONN->select('*')->from('tp_sablony_typy')->where("id=$id");
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
	 * Shows type operations in Sablona
	 * @param type $id = id_sablona
	 * @return type
	 */
	public function showSablTypOper($id)
	{
		return $this->CONN->query("SELECT st.id [id], st.id_tp_sablony [idts], st.id_typy_operaci [idto], st.nazev, 
								tt.zkratka [tzkratka], tt.nazev [tnazev],
								dd.zkratka [dzkratka], dd.nazev [dnazev],
								ts.zkratka [szkratka], ts.nazev [snazev],
								st.poradi
								FROM tp_sablony_typy st
								LEFT JOIN typy_operaci tt ON st.id_typy_operaci = tt.id
								LEFT JOIN druhy_operaci dd ON tt.id_druhy_operaci = dd.id
								LEFT JOIN tp_sablony ts ON st.id_tp_sablony = ts.id
								WHERE ts.id = $id
								ORDER BY st.poradi")->fetchAll();
	}


	public function updateTypo($id, $data = array())
	{
		return $this->CONN->update('tp_sablony_typy', $data)->where("id=$id")->execute();
	}
	
	public function insertTypo($data = array())
	{
		return $this->CONN->insert('tp_sablony_typy', $data)->execute();
	}
	
	public function deleteTypo($id)
	{
		return $this->CONN->delete('tp_sablony_typy')->where("id=$id")->execute();
	}
	
}


