<?php

/**
 * 
 */


class FilterModel extends Model
{

	/** @var     DibiConnection */
	private $table = 'u_filters';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }

	/**
	 * 
	 * Vrácení filtru pro presenter/render
	 * @param type $id_user
	 * @param type $presenter
	 * @param type $render
	 * @return type
	 */
	public function getUserFilter($id_user, $presenter, $render='default'){
		return $this->CONN->dataSource("SELECT filter FROM $this->table 
								WHERE id_users=$id_user AND presenter='$presenter' AND render='$render'"
							);
	}
	
	/**
	 * Uložení / aktualizace filtru pro daný render a user v DB
	 * @param type $id_user
	 * @param type $presenter
	 * @param type $render
	 * @param type $filter
	 * @return type
	 */
	public function setUserFilter($id_user, $presenter, $render='default', $filter='')
	{
		$result = $this->CONN->query("SELECT count(*) FROM $this->table 
								WHERE id_users=$id_user AND presenter='$presenter' AND render='$render'");
		$cnt = $result->fetchSingle();
		$data = array('id_users'=>$id_user,'presenter'=>$presenter,'render'=>$render,'filter'=>$filter);
		if ($cnt>0) {
			return $this->CONN->update($this->table, $data)->where("id_users=$id_user AND presenter='$presenter' AND render='$render'")->execute();
		} else {
			return $this->CONN->insert($this->table, $data)->execute();
		} 
	}	

	/**
	 * Vrací string jako část podmínky pro výběr dat z dotazu
	 * @param type $fields
	 * @param type $filter
	 * @return string
	 */
	public function setCondFilter($fields, $filter){
		$p1 = strpos($filter, "/"); // implementace OR
		$ret = '';
		if($p1 !== FALSE){
			$fs = explode("/",$filter);
			foreach ($fs as $f) {
				if($ret<>""){$ret .= " OR ";}
				$ret .= $fields . " LIKE '%$f%'";
			}
		} else {
			$ret = $fields . " LIKE '%$filter%'";
		}
		return $ret;
	}
		
}
