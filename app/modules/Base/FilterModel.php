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
	
	
	public function setUserFilter($id_user, $presenter, $render='default', $filter='')
	{
		$result = $this->CONN->query("SELECT count(*) FROM $this->table 
								WHERE id_users=$id_user AND presenter='$presenter' AND render='$render'");
		$cnt = $result->fetchSingle();
		$data = array('id_users'=>$id_user,'presenter'=>$presenter,'render'=>$render,'filter'=>$filter);
		if ($cnt>0)
		{
			return $this->CONN->update($this->table, $data)->where("id_users=$id_user AND presenter='$presenter' AND render='$render'")->execute();
		} else {
			return $this->CONN->insert($this->table, $data)->execute();
		} 
	}	
		
}
