<?php

use Nette\Object;
/**
 * Model Pocet class
 */
class AtrCasu extends Model
{
	/**
	 *  @var string
	 *  @table
	 */
	private $table = 'atr_casu';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * 	Vrací atributy času 
	 * @param int
	 * @return record set
	 */
	public function show()
	{
		return $this->CONN->select('*')->from($this->table);
	}

	
	/**
	 * 	Vrací atributy času s přiřazením
	 * @param int
	 * @return record set
	 */
	public function showto()
	{
		return $this->CONN->dataSource("
				SELECT	a.id
						, a.zkratka
						, a.nazev
						, a.typ
						, ROUND(a.cas_sec,3) [cas_sec]
						, t.zkratka [tozkr]
						, t.nazev [tonaz] 
						, t.id [tid]
				FROM atr_casu a
				LEFT JOIN atr_typy_oper ao ON a.id=ao.id_atr_casu
				LEFT JOIN typy_operaci t ON ao.id_typy_operaci=t.id
			");
	}

		
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		return $this->CONN->select('id, zkratka, nazev, typ, ROUND(cas_sec,3) [cas_sec]')->from($this->table)->where('id=%i', $id);
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
	 * Inserts data to the assign table
	 * @param array
	 * @return Identifier
	 */
	public function insertATO($data = array())
	{
		return $this->CONN->insert('atr_typy_oper', $data)->execute();
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
	 * Deletes recorda assigns to typed oper
	 * @param int
	 * @return mixed
	 */
	public function deleteATO($id_atr)
	{
		return $this->CONN->delete('atr_typy_oper')->where('id_atr_casu=%i', $id_atr)->execute();
	}
	
	/**
	 * 	Vrací vybrané sloupce pro hromadné přiřazení atributu času typovým operacím
	 * @return record set
	 */
	public function getTypesOper($ida)
	{
		return $this->CONN->dataSource("
				SELECT t.nazev [nazev], t.id [idto], d.zkratka, t.zkratka [tzkratka], 
						 case when a.id is null then 'false' else 'true' end [yes]
				FROM typy_operaci t 
				LEFT JOIN druhy_operaci d ON t.id_druhy_operaci=d.id
				LEFT JOIN atr_typy_oper a ON t.id=a.id_typy_operaci and a.id_atr_casu=$ida 
				LEFT JOIN atr_casu c ON a.id_atr_casu=c.id
								");
	}

	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insertGroupa($data = array(), $id_atr, $pocet=0)
	{
		$cnt=0;
		if ($id_atr>0){
			// vymazat aktualni prirazeni
			$this->deleteATO($id_atr);
			for($i=1; $i<=$pocet; $i++){
				$adata = $data[$i];
				if($adata){
					//vlozit radek s prireazenim
					$this->insertATO($adata);
					$cnt++;
				}
				unset($adata);
			}
			return $cnt;
		}
	}	
	
}


