<?php

use Nette\Object;
/*
 * Model Nabidka class
 */
class Nabidka extends Model // DibiRow obstará korektní načtení dat
{
	/* @var string
	 * @table
	 */
	private $table = 'nabidky';
	/** @var Nette\Database\Connection */ 
	public $connection;

	private $full_detail_query = "SELECT n.id [id], n.pozad_datum, n.prij_datum, n.popis [popis], n.poznamka [poznamka], 
									n.id_set_sazeb, n.id_set_sazeb_o, n.id_firmy [id_firmy], RTRIM(n.folder) [folder],
									f.zkratka [zfirma], f.nazev [firma], s.id_stav, s.zkratka [stav], s.popis [nstav], 
									s.username, s.uzivatel, s.id_user, s.datzmeny, 
									ss.nazev [sets], ss.id [sid], so.nazev [seto], so.id [oid]
									FROM nabidky n
									LEFT JOIN firmy f ON n.id_firmy = f.id
									LEFT JOIN set_sazeb ss ON n.id_set_sazeb=ss.id
									LEFT JOIN set_sazeb_o so ON n.id_set_sazeb_o=so.id
									LEFT JOIN
									(SELECT sn.id_nabidky, sn.datum_zmeny [datzmeny], st.zkratka, st.popis, st.id [id_stav],
										u.username, u.jmeno+' '+u.prijmeni [uzivatel], u.id [id_user],
										ROW_NUMBER() OVER (PARTITION BY sn.id_nabidky ORDER BY sn.datum_zmeny DESC) AS rn
										FROM stav_nabidka sn
										LEFT JOIN stav st ON sn.id_stav = st.id
										LEFT JOIN users u ON sn.id_user = u.id
									 ) s ON n.id = s.id_nabidky AND rn = 1";
	

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
		if($this->filter<>''){
			$sql_cmd = $this->full_detail_query . "	WHERE Convert(varchar, n.popis) + f.nazev LIKE '%$this->filter%'";
			
		} else {
			$sql_cmd = $this->full_detail_query;
		}

		if($this->limit==0 && $this->offset==0){
			return $this->CONN->query($sql_cmd);
		} else {
			$sql_pgs = $this->pagedSql($sql_cmd, '', 'n.id DESC');
			return $this->CONN->query($sql_pgs);
		}
				
	}
	
	/**
	 * 	Vrací atributy tabulky pro konkrétní firmu
	 *  @return record set
	 */
	public function showOffer($id_firmy)
	{
		return $this->CONN->dataSource("SELECT n.*,f.nazev [nfirma], f.id [idf] FROM nabidky n
                                LEFT JOIN firmy f ON n.id_firmy=f.id
                                WHERE n.id_firmy = $id_firmy");
	}
	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */	
	public function find($id)
	{
		return $this->CONN->query($this->full_detail_query . "	WHERE n.id=$id");
	}

	/**
	 * Vrací data pro konkrétní záznam dle id_firmy
	 * @param int
	 * @return record set
	 */	
	public function findByIdOffer($id)
	{
		return $this->CONN->query($this->full_detail_query . "	WHERE n.id=$id");
	}

	/**
	 * Vrací data pro konkrétní záznam dle id_firmy
	 * @param int
	 * @return record set
	 */	
	public function findByCompany($id_firmy)
	{
		$sql_cmd = $this->full_detail_query . "	WHERE n.id_firmy=$id_firmy";
		if($this->limit==0 && $this->offset==0){
			return $this->CONN->query($sql_cmd. " ORDER BY n.id DESC");
		} else {
			$sql_pgs = $this->pagedSql($sql_cmd, '', 'n.id DESC');
			return $this->CONN->query($sql_pgs);
		}		
	}
	
	/**
	 *
	 * @param type $limitStatus  .. number of id status
	 * @param type $isIn .. NOT = id NOT IN list of id status, "" = is in list
	 * @return type records
	 */
	public function showByStatus($limitStatus = 0, $isIn="NOT"){
		$cond = "";
		if($limitStatus>0){
			$cond=" WHERE id_stav IN ($limitStatus, 11, 21)";	//zadana, odmitnuta a uzamcena
		}
		$sql = "$this->full_detail_query WHERE 
						n.id $isIn IN (SELECT id_nabidky FROM stav_nabidka $cond)";
		return $this->CONN->query( $sql )->fetchAll();
	}	
	
	/**
	 * Statistika stavů nabídky v rámci kategorie
	 * @return type 
	 */
	public function getSummary(){
		return $this->CONN->query("SELECT CAST(s.popis AS varchar) [name], s.zkratka2 [category], COUNT(*) [value]
								FROM stav_nabidka sn
									LEFT JOIN stav s ON sn.id_stav=s.id
								GROUP BY id_stav, CAST(s.popis AS varchar), s.zkratka2
								ORDER BY id_stav");
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
	 * Insert status of offer
	 * @param int int id_nabidky, typ stavu / viz číselník stavy
	 * @return mixed
	 */
	public function insertStatus($id_nabidky, $status, $id_user)
	{
		if($id_nabidky==0){return false;}
		//("INSERT id_nabidky, id_stav, datum_zmeny, id_user");
		$result = $this->CONN->query("SELECT count(*) FROM stav_nabidka WHERE id_nabidky=$id_nabidky AND id_stav=$status");
		$cnt = $result->fetchSingle();
		if ($cnt>0)
		{
			//mozna update?? co, pokud se vrati stav o krok zpet?? asi delete
			$this->CONN->query("DELETE FROM stav_nabidka WHERE id_nabidky=$id_nabidky AND id_stav=$status");
		} 
		if ($status == 21){
			//vymazat případný status "ODEMČENO"
			$this->CONN->query("DELETE FROM stav_nabidka WHERE id_nabidky=$id_nabidky AND id_stav=22");
		}
		if ($status == 22){
			//vymazat případný status "UZAMČENO"
			$this->CONN->query("DELETE FROM stav_nabidka WHERE id_nabidky=$id_nabidky AND id_stav=21");
		}

		$data = array('id_nabidky' => $id_nabidky, 'id_stav' => $status, 'datum_zmeny' => date("Ymd H:i:s"), 'id_user' => $id_user );
		return $this->CONN->insert('stav_nabidka', $data)->execute();

	}
	
	/**
	 * Erase status of offer
	 * @param int int id_nabidky, id_stavu, id_user
	 * @return mixed
	 */
	public function deleteStatus($ioffer=0, $istat=0, $iuser=0)
	{
		if($ioffer>0 && $istat>0 && $iuser>0){
			return	$this->CONN->query("DELETE FROM stav_nabidka WHERE id_nabidky=$ioffer AND id_stav=$istat AND id_user=$iuser");
		}

	}
	
	/**
	 * Sumarizace množství
	 * @param type $id
	 * @return type 
	 */
	public function sumVolume($id, $typ = 0){
		$sql_cmd = "SELECT c.id_nabidky, 
									SUM(CASE WHEN c.id_typy_cen=8 THEN hodnota ELSE (hodnota * p.mnozstvi) END) [objem],
									p.mnozstvi [pocty]
									--,COUNT(c.id_produkty) [pprod]
									FROM ceny c
										LEFT JOIN pocty p ON c.id_pocty=p.id
									WHERE c.id_typy_cen in (8,10) AND c.id_nabidky = $id AND c.aktivni=1
									GROUP BY c.id_nabidky, p.mnozstvi, c.id_meny";
		if($typ==0){
			$sql_cmd = "SELECT id_nabidky, SUM(objem) [objem], SUM(pocty) [pocty] FROM (" 
						. $sql_cmd
						. ") a GROUP BY a.id_nabidky";
		}
		return $this->CONN->dataSource($sql_cmd);
			
	}
	
	
	/**
	 * Copy offer include products, materials, operations
	 * @param type $id
	 * @param type $id_user
	 * @return type 
	 */
	public function copyNabidka($id, $id_user)
	{
		if($id>0 && $id_user>0){
			$res = $this->CONN->query("
								DECLARE @id_nab int;
								EXECUTE copyOffer $id, $id_user, @id_nab OUTPUT;
								SELECT @id_nab [nid];
								")->fetch();
			return $res->nid;
		} else {
			return false;
		}

	}
	
	
	
}


