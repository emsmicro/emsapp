<?php

use Nette\Object;
/**
 * Model Sazba class
 */

class Sazba extends Model 
{
	/**
	 * @var string
	 * @table
	 */
	private $table = 'sazby';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * Vrací vybrané atributy tabulky pro konkrétní záznam/nebo všechny atributy setů sazeb
	 * @param int 
	 * @return record set
	 */
	public function show($id = 0, $id_produkty = 0)
	{
		$specif = "WHERE sa.id_set_sazeb=$id";
		if($id==0){
			if($id_produkty>0)
				$specif = "WHERE sa.id_set_sazeb IN 
							(SELECT DISTINCT COALESCE(c.id_set_sazeb, n.id_set_sazeb) [idss]
								FROM ceny c
								LEFT JOIN nabidky n ON c.id_nabidky = n.id
								WHERE id_produkty=$id_produkty)";
			else {
				$specif = "";
			}
			
		}
		$res = $this->CONN->query("
				SELECT sa.id_set_sazeb [idss], ts.id [tid], ts.nazev [typ], sa.id [sid], 
						ROUND(sa.hodnota*100,2) [hodnota], ts.zkratka, ts.poradi, sa.pravidlo
							FROM typy_sazeb ts
							LEFT JOIN sazby sa ON ts.id=sa.id_typy_sazeb
							$specif 
							ORDER BY idss, poradi			
								")->fetchAll();
		//dd($ret,"ASSOC pole");
//		return array($res, $ret);
		return $res;
	}

	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */	
	public function find($id)
	{
		//return $this->CONN->select('*')->from($this->table)->where('id=%i', $id);
		return $this->CONN->dataSource('SELECT ts.nazev, ROUND(s.hodnota*100,2) [hodnota], ts.zkratka, s.pravidlo
									FROM sazby s LEFT JOIN typy_sazeb ts ON s.id_typy_sazeb=ts.id  
									WHERE s.id='.$id
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
	
	/**
	 * Returns specific type of rate
	 * @param int
	 * @return mixed
	 */
	public function getTypSazby($id) {
		$pom = $this->CONN->fetchPairs('SELECT nazev FROM typy_sazeb WHERE id='.$id);
		return $pom[0];
	}
	
	/**
	 * Select all typy_sazeb by set_sazeb
	 * 
	 * @param type $id_set_sazeb
	 * @return type
	 */
	public function getTypeRates($id_set_sazeb)
	{
		return $this->CONN->dataSource("SELECT ts.id, ts.zkratka, ts.nazev, ts.poradi, ss.hodnota, COALESCE(ss.id,0) [ids], ss.pravidlo
									FROM typy_sazeb ts
									LEFT JOIN sazby ss ON ts.id = ss.id_typy_sazeb
									WHERE ss.id_set_sazeb = $id_set_sazeb OR ss.id_set_sazeb is null
								");
		 
	}
	
	/**
	 * Vrací hodnotu sazby dle zkratky typu pro danou nabídku
	 * @param type $id_nabidky
	 * @param type $zkr_sazby
	 * @return type
	 */
	public function getRateByType($id_nabidky, $zkr_sazby)
	{
		return $this->CONN->query("
				SELECT sa.hodnota [sazba]
				FROM nabidky na
				LEFT JOIN set_sazeb ss ON na.id_set_sazeb = ss.id
				LEFT JOIN sazby sa ON ss.id = sa.id_set_sazeb
				LEFT JOIN typy_sazeb st ON sa.id_typy_sazeb = st.id
				WHERE na.id = $id_nabidky AND st.zkratka = '$zkr_sazby' 
								")->fetchSingle();
		 
	}	
	
	public function getIdTypeRates()
	{
		return $this->CONN->fetchPairs("SELECT id, nazev
									FROM typy_sazeb
									ORDER BY id
									");
		 
	}
	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insertGroup($data = array(), $idss=0, $pocet=0)
	{
			$cnt=0;
			//stavajici sazby nejprve smazeme
			$this->CONN->delete($this->table)->where('id_set_sazeb=%i', $idss)->execute();
			for($i=1; $i<=$pocet; $i++){
				$opdata = (array) $data[$i];
				if($opdata){
						$this->insert($opdata);
						$cnt++;
				}
				unset($opdata);
			}
		return $cnt;
	}
	
	/**
	 * Ukládá set sazeb hromadně
	 * @param type $data	- mpole z formulare pro hromadnou zmenu
	 * @param type $idss	- id_set_sazeb rezii
	 * @param type $new		= 1 .. zalozit kopii setu sazeb, = 0 .. jen ulozit zmeny
	 * @return string
	 */
	public function saveGroupRate($data, $idss, $new=0) 
	{
		$ret['id']=$idss;
		$ret['message'] = "";
		if($new > 0){
			// nutno zalozit kopii setu sazeb a pak ulozit data jako nova
			$ss = new SetSazeb();
			$rowa = $ss->find($idss)->fetch();
			if(!$rowa){
				$ret['message'] = "Není možné založit kopii setu sazeb, zdrojový set sazeb nebyl nalezen.";
				return $ret;
			}
			$sdata['nazev'] = 'KOPIE: '.$rowa->nazev;
			$sdata['platnost_od'] = date("Y-m-d");
			$sdata['platnost_do'] = null;
			$sdata['kalkulace'] = $rowa->kalkulace;
			$idss = $ss->insert($sdata);
			$ret['id'] = $idss;
		}
		// zpracovani dat
		$rows  = $data;
		$gdata = array();
		$idata = array();
		$j = 0;
		$r = 0;
		$h = 0;
		$p = '';
		$h0 = 0;
		$p0 = '';
		$idts = 0;
		$ids = 0;
		foreach($rows as $k => $v ){
			$j++;
			switch($j){
				case 1:
					$h = floatval($v);
				case 2:
					$p = $v;
				case 3:
					$h0 = floatval($v);
				case 4:
					$p0 = $v;
				case 5:
					$idts = intval($v);
				case 6:
					$ids = intval($v);
			}
			if($j == 6){
				if ($h <> $h0 or $p<>$p0 or $new>0){
					$r++;
					$idata[$r]['ids']			= $new==0 ? $ids : 0;
					$gdata[$r]['hodnota']		= ($h/100);
					$gdata[$r]['pravidlo']		= $p;
					$gdata[$r]['id_typy_sazeb'] = $idts;
					$gdata[$r]['id_set_sazeb']	= (int) $idss;
				}
				$j = 0;
				$h = 0;
				$p = '';
				$h0 = 0;
				$p0 = '';
				$idts = 0;
				$ids = 0;
			}
		}
		if($r > 0){
			$pocet = $this->insUpdGroup($gdata, $idata, $idss, $r);
			$instext = "";
			if($pocet['i'] > 0){$instext = ", vloženo ".$pocet['i'];}
			$ret['message'] = "Bylo aktualizováno ".$pocet['u'].$instext." záznamů sazeb režií (či jejich pravidel).";
		} else {
			$ret = 'Hromadné uložení sazeb režií nebylo provedeno, neboť nebyly změněny žádné údaje.';
		}
		return $ret;
	}
	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insUpdGroup($data = array(), $idata = array(), $idss = 0, $pocet = 0)
	{
		$c = array();
		$c['i'] = 0;
		$c['u'] = 0;
		$c['T'] = 0;
		if ($idss > 0){
			for($i = 1; $i <= $pocet; $i++){
				$adata = $data[$i];
				if($adata){
					$r = $this->insertSR($adata, $idata[$i]['ids']);
					if ($r){$c['T']++;}
					if ($r[0]==1){$c['i']++;}
					if ($r[0]==2){$c['u']++;}
				}
				unset($adata);
			}
		}
		return $c;
	}
	
	public function insertSR($data = array(), $id_sazby = 0)
	{
		$p = array();
		if($id_sazby == 0){
			$id_sazby = $this->insert($data);
			$p[0] = 1;
		} else {
			$this->update($id_sazby, $data);
			$p[0] = 2;
		}
		$p[1] = $id_sazby;
		return $p;
	}
	
	
}


