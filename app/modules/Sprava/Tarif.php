<?php

use Nette\Object;
/**
 * Model Sazeb operaci class
 */
class Tarif extends Model // DibiRow obstará korektní načtení dat
{
	/**
	 *  @var string
	 * @table
	 */
	private $table = 'tarify';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }

	/**
	 * Vrací vybrané sloupce
	 * @param int
	 * @return record set
	 */
	public function show($id)
	{
		return $this->CONN->query("SELECT tt.*, ta.id [tid], ROUND(ta.tarif,4) [tarif], ROUND(ta.hodnota,4) [hodnota], 
										ta.id_set_tarifu [idss], calc=0, perc=0, graf=''
								FROM typy_tarifu tt
								LEFT JOIN (SELECT * FROM tarify WHERE id_set_tarifu=$id) ta ON tt.id=ta.id_typy_tarifu
								ORDER BY poradi"
								);
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
	 * Vrací název konkrétního typu operace
	 * @param int
	 * @return string
	 */
	public function getTypTarifu($id) {
		$pom = $this->CONN->fetch('SELECT nazev FROM typy_tarifu WHERE id='.$id);
		return $pom;
	}
	
	/**
	 * 	Vrací vybrané sloupce pro hromadné zadávání operací
	 * @return record set
	 */
	public function getTypesTarif($id_set_tarifu)
	{
		return $this->CONN->query("SELECT COALESCE(ta.id,0) [idso], tt.zkratka, tt.nazev, tt.id [idto], tt.poradi, ta.hodnota, ta.tarif
									FROM typy_tarifu tt
									LEFT JOIN tarify ta ON tt.id = ta.id_typy_tarifu AND ta.id_set_tarifu = $id_set_tarifu
									WHERE ta.id_set_tarifu = $id_set_tarifu OR ta.id_set_tarifu is null
									ORDER BY tt.poradi
								");
	}

	public function getIdTypesTarif()
	{
		return $this->CONN->fetchPairs("SELECT id, nazev FROM typy_tarifu	ORDER BY poradi");
	}
	

	/**
	 * Ukládá set tarifu hromadně
	 * @param type $data	- mpole z formulare pro hromadnou zmenu
	 * @param type $idss	- id_set_tarifu
	 * @param type $new		= 1 .. zalozit kopii setu tarifu, = 0 .. jen ulozit zmeny
	 * @return string
	 */
	public function saveGroupRate($data, $idss, $new=0) 
	{
		$ret['id']=$idss;
		$ret['message'] = "";
		if($new > 0){
			// nutno zalozit kopii setu sazeb a pak ulozit data jako nova
			$ss = new SetTarifu();
			$rowa = $ss->find($idss)->fetch();
			if(!$rowa){
				$ret['message'] = "Není možné založit kopii setu tarifů, zdrojový set tarifů nebyl nalezen.";
				return $ret;
			}
			$sdata['nazev'] = 'KOPIE: '.$rowa->nazev;
			$sdata['platnost_od'] = date("Y-m-d");
			$sdata['platnost_do'] = null;
			$idss = $ss->insert($sdata);
			$ret['id'] = $idss;
		}
		
		// zpracovani dat
		$tar = new Tarif;
		$rows = $data;
		$gdata = array();
		$idata = array();
		$j = 0;
		$r = 0;
		$t = 0;
		$t0 = 0;
		$h = 0;
		$h0 = 0;
		$idto = 0;
		$idso = 0;
//		dd($rows,'DATA');
//		exit();
		foreach($rows as $k => $v ){
			$j++;
			switch($j){
				case 1:
					$t = floatval($v);
				case 2:
					$h = floatval($v);
				case 3:
					$t0 = floatval($v);
				case 4:
					$h0 = floatval($v);
				case 5:
					$idto = intval($v);
				case 6:
					$idso = intval($v);
			}
			if($j == 6) {
				if ($h <> $h0 or $t <> $t0 or $new>0){
					$r++;
					$idata[$r]['idso'] = $new==0 ? $idso : 0;
					$gdata[$r]['tarif'] = $t;
					$gdata[$r]['hodnota'] = $h;
					$gdata[$r]['id_typy_tarifu'] = $idto;
					$gdata[$r]['id_set_tarifu'] = (int) $idss;
				}
				$j = 0;
				$t = 0;
				$t0 = 0;
				$h = 0;
				$h0 = 0;
				$idto = 0;
				$idso = 0;
			}
		}
		if($r > 0){
			$pocet = $this->insUpdGroup($gdata, $idata, $idss, $r);
			$instext = "";
			if($pocet['i'] > 0){$instext = ", vloženo ".$pocet['i'];}
			$ret['message'] = "Bylo aktualizováno ".$pocet['u'].$instext." záznamů tarifů.";
		} else {
			$ret = 'Hromadné uložení tarifů nebylo provedeno, neboť nebyly změněny žádné údaje.';
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
					$r = $this->insertST($adata, $idata[$i]['idso']);
					if ($r[0]==1){$c['i']++;}
					if ($r[0]==2){$c['u']++;}
					if($r){$c['T']++;}
				}
				unset($adata);
			}
		}
		return $c;
	}

	public function insertST($data = array(), $id_tarif = 0)
	{
		$p = array();
		if($id_tarif == 0){
			$id_tarif = $this->insert($data);
			$p[0] = 1;
		} else {
			$this->update($id_tarif, $data);
			$p[0] = 2;
		}
		$p[1] = $id_tarif;
		return $p;
		
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
			$this->CONN->delete($this->table)->where('id_set_tarifu=%i', $idss)->execute();
			for($i=1; $i<=$pocet; $i++){
				$opdata = $data[$i];
				if($opdata){
					$this->insert($opdata);
					$cnt++;
				}
				unset($opdata);
			}
		return $cnt;
	}
	
	private function calculate($data = array())
	{
		foreach ($data as $key => $value) {
			if($value==""){$data[$key]=0;}
		}
		dd($data);
		exit();
			$data['sazba_instalace']	= $data['sazba_instalace']/100;
			$data['vytizeni']			= $data['vytizeni']/100;
			$data['vyuziti_prikonu']	= $data['vyuziti_prikonu']/100;
			$data['naklady_udrzba']		= $data['naklady_udrzba']/100;
			$data['naklady_ostatni']	= $data['naklady_ostatni']/100;
			if ($data['stari']==""){$data['stari']=0;}
			if ($data['spotreba_dusiku']==""){$data['spotreba_dusiku']=0;}
			$data['kapacita']		= $param['stroj_kapcita_sm'] * $data['smennost'] * $data['vytizeni'];
			
			$investice = $data['poriz_cena'] * (1 + $data['sazba_instalace']);
			$cenapenez = $investice * $param['urokova_mira']/100;
			$naklploch = $data['plocha'] * $param['naklady_plochy'];
					
			$data['odpisy_hod']		= $investice / $data['doba_odpisu'] / $data['kapacita'];
			$data['naklady_fixni']	= $data['odpisy_hod'] + ($cenapenez + $naklploch) / $data['kapacita'];
			
			$elektrina	= $data['prikon'] * $data['vyuziti_prikonu'] * $param['cena_elekriny'];
			$dusik		= $data['spotreba_dusiku'] * $param['cena_dusiku'];
			$varnakost	= ($data['naklady_udrzba'] + $data['naklady_ostatni']) * $investice / $data['kapacita'];
		
			$data['naklady_variabilni'] = $elektrina + $dusik + $varnakost;
			
			$data['hodinova_cena'] = $data['naklady_fixni'] + $data['naklady_variabilni'];
			
			return $data;
	}
	
	
	
	
	
	
	
}


