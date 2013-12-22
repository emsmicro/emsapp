<?php

use Nette\Object;
/**
 * Model Pocet class
 */
class Prava extends Model
{
	/**
	 *  @var string
	 *  @table
	 */
	private $table = 'role';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	
	/**
	 * 	Vrací role s prirazenim prav
	 * @param int
	 * @return record set
	 */
	public function show()
	{
		return $this->CONN->dataSource("
						SELECT	DISTINCT
								r.id [idr]
								, r.nazev [role]
								, r.popis [prole]
								, p.id [idp]
								, p.modul
								, p.presenter
								, p.poradi
								, p.funkce
								, p.popis
								, a.*
							FROM role r
							LEFT JOIN prava a ON r.id = a.id_role
							LEFT JOIN permission p ON a.id_permission = p.id
							WHERE r.nazev<>'Admin'
			");
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
		return $this->connection->insert($this->table, $data)->execute(dibi::IDENTIFIER);
	}

	/**
	 * Inserts data to the prava table
	 * @param array
	 * @return Identifier
	 */
	public function insertATR($data = array())
	{
		return $this->CONN->insert('prava', $data)->execute();
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
	 * Delete record assigns to typed oper
	 * @param int
	 * @return mixed
	 */
	public function deleteATR($id_role, $modul='')
	{
		if($modul==''){
			return $this->CONN->delete('prava')->where('id_role=%i', $id_role)->execute();
		} else {
			return $this->CONN->query("DELETE FROM prava
									WHERE id_role = $id_role
										AND id_permission IN (SELECT id FROM permission WHERE modul = '$modul')
								");
		}
	}
	
	/**
	 * 	Vrací vybrané sloupce pro hromadné přiřazení práv akcím
	 * @return record set
	 */
	public function getRights($idr, $modul='')
	{
		$qry = "SELECT DISTINCT p.*, case when a.id_role is null then 'false' else 'true' end [yes], r.id [idr], r.nazev [role], r.popis [prole], cp.cntp
				FROM permission p 
				LEFT JOIN prava a ON p.id=a.id_permission AND a.id_role=$idr 
				LEFT JOIN role r ON a.id_role=r.id
				LEFT JOIN (SELECT modul, presenter, count(presenter) [cntp]
					FROM permission GROUP BY modul, presenter) cp 
					ON p.modul=cp.modul AND p.presenter=cp.presenter
				";
		if($modul<>''){
			$cond = " WHERE p.modul = '$modul'";
		} else {
			$cond = "";
		}
		return $this->CONN->dataSource($qry . $cond);
	}

	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insertRights($data = array(), $id_role=0, $pocet=0, $modul='')
	{
		$cnt=0;
		if ($id_role>0){
			// vymazat aktualni prava
			$this->deleteATR($id_role,$modul);
			for($i=1; $i<=$pocet; $i++){
				$adata = $data[$i];
				if($adata){
					//vlozit radek s prireazenim
					$this->insertATR($adata);
					$cnt++;
				}
				unset($adata);
			}
			return $cnt;
		}
	}	
	
	public function getResources()
	{
		$data = $this->CONN->select('modul,presenter,funkce,poradi')->from('permission')->orderBy('modul,presenter,poradi')->fetchAll();
		return $data;
	}
	/**
	 * Vrátí práva dle role
	 * @param type $id_role
	 * @return type 
	 */
	public function getPermissions($role='')
	{
		if($role<>''){$cond = " WHERE r.nazev='$role'";} else {$cond='';}
		$qry = "SELECT r.nazev [role], modul, presenter, funkce 
					FROM prava pr
					LEFT JOIN permission p ON pr.id_permission = p.id
					LEFT JOIN role r ON pr.id_role = r.id 
					$cond
				";
		return $this->CONN->dataSource($qry);
	}

	/**
	 * Vrátí role
	 * @return type 
	 */
	public function getRole()
	{
		$qry = "SELECT nazev [role] FROM role";
		return $this->CONN->dataSource($qry);
	}
	
}


