<?php

use Nette\Object;
/**
 * Model Operace class
 */
class Operace extends Model // DibiRow obstará korektní načtení dat
{
	/**
	 *  @var string
	 * @table
	 */
	private $table = 'operace';


    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * 
	 * @param type $id_produktu
	 * @param type $notempl .. nemá šablonu a postup
	 * @return type
	 */
	public function show($id_produktu=0, $notempl = false, $id_tpostup=0)
	{
		$cond = "";
		if($notempl){
			$cond = " AND (o.id_sablony is null OR o.id_tpostup is null) ";
		}
		return $this->CONN->query("SELECT o.*, COALESCE(o.poradi, tp.poradi) [oporadi], COALESCE(o.id_tpostup, $id_tpostup) [id_tpostup],
								tp.nazev, tp.poradi [tporadi], a.pocet, dr.zkratka [druh] 
							FROM operace o 
							LEFT JOIN typy_operaci tp ON o.id_typy_operaci=tp.id
							LEFT JOIN druhy_operaci dr ON tp.id_druhy_operaci=dr.id
							LEFT JOIN vazby v ON o.id=v.id_operace 
							LEFT JOIN 
								(SELECT id_typy_operaci, COUNT(id) [pocet] FROM atr_typy_oper
									GROUP BY id_typy_operaci
								) a
									 ON a.id_typy_operaci = tp.id
							WHERE v.id_vyssi = $id_produktu $cond 
							ORDER BY oporadi");
	}
	
	/**
	 * Vypíše všechny operace s oceněním nákladů
	 * @param type $id_produktu
	 * @param type $id_nabidky
	 * @return type
	 */
	public function showNaklady($id_produktu, $id_nabidky) {
		return $this->CONN->query("SELECT o.*, COALESCE(o.poradi, tp.poradi) [oporadi], COALESCE(o.id_tpostup, 0) [id_tpostup],
								tp.nazev, COALESCE(tp.poradi, o.poradi) [tporadi], a.pocet, dr.zkratka [druh],
								o.ta_cas*sz.hodnota/60 [ta_naklad], o.tp_cas*sz.hodnota/60 [tp_naklad]
							FROM operace o 
							LEFT JOIN typy_operaci tp ON o.id_typy_operaci=tp.id
							LEFT JOIN druhy_operaci dr ON tp.id_druhy_operaci=dr.id
							LEFT JOIN sazby_o sz ON tp.id = sz.id_typy_operaci
							LEFT JOIN nabidky na ON sz.id = na.id_set_sazeb_o AND na.id = $id_nabidky
							LEFT JOIN vazby v ON o.id=v.id_operace 
							LEFT JOIN 
								(SELECT id_typy_operaci, COUNT(id) [pocet] FROM atr_typy_oper
									GROUP BY id_typy_operaci
								) a
									 ON a.id_typy_operaci = tp.id
							WHERE v.id_vyssi = $id_produktu 
							ORDER BY tporadi");		
	}
	
	/**
	 * Sumarizace kapacit dle druhu a typu operace
	 * @param type $id_produktu
	 * @param type $id_nabidky
	 * @return type
	 */
	public function sumKapacitaDruh($id_produktu, $id_nabidky)
	{
//		$ks = $this->CONN->query("SELECT vyrobni_davka [davka], mnozstvi FROM pocty 
//									WHERE id_produkty = $id_produktu AND id_nabidky = $id_nabidky")->fetch();
//		if($ks){
//			$davka = $ks->davka;
//			$pocet = $ks->mnozstvi;
//		} else {
//			$davka = 1;
//			$pocet = 0;
//		}
		return $this->CONN->query("
					SELECT	
							v.id_vyssi [idp], 
							d.poradi [poradi],
						--	d.id [idd], 
							CASE WHEN d.zkratka = 'Strojní' THEN d.zkratka + ' - ' + p.zkratka ELSE d.zkratka END [druh],
						--	d.zkratka [druh],
						--	p.zkratka [typ],
						--	a.id,
							a.davka,
							a.pocet,
							SUM(o.ta_cas) [TA], 
							SUM(o.tp_cas) [TP], 
							SUM(o.naklad) [NA],
							SUM((o.ta_cas * a.pocet + o.tp_cas * a.pocet/a.davka)/60) [TC] 
						FROM operace o 
						LEFT JOIN typy_operaci p ON o.id_typy_operaci=p.id
						LEFT JOIN druhy_operaci d ON p.id_druhy_operaci=d.id
						LEFT JOIN vazby v ON o.id=v.id_operace 
						LEFT JOIN (SELECT id_produkty, id_nabidky, vyrobni_davka [davka], mnozstvi [pocet], ROW_NUMBER () OVER (PARTITION BY [id_produkty] 
									ORDER BY id_nabidky, id_produkty, mnozstvi DESC) rn FROM pocty
									) AS a ON v.id_vyssi = a.id_produkty AND rn=1
						WHERE	v.id_vyssi = $id_produktu AND a.id_nabidky = $id_nabidky
								AND (o.ta_cas+o.tp_cas)*100>0
						GROUP BY 
							v.id_vyssi, 
							d.poradi,
						--	d.id, 
							CASE WHEN d.zkratka = 'Strojní' THEN d.zkratka + ' - ' + p.zkratka ELSE d.zkratka END,
						--	d.zkratka,
						--	p.zkratka,
						--	p.poradi,
						--	a.id
							a.davka,
							a.pocet
						ORDER BY d.poradi --, p.poradi
				")->fetchAll();
	}
	
	/**
	 * Sumarizuje potřebu kapacit za celou nabídku
	 * @param type $id_nabidky
	 * @return type
	 */
	public function sumKapacitaNabOld($id_nabidky)
	{
		return $this->CONN->query("	
					SELECT	
							d.poradi [poradi],
							CASE WHEN d.zkratka = 'Strojní' THEN d.zkratka + ' - ' + p.nazev ELSE d.zkratka END [druh],
							SUM(o.ta_cas) [TA], 
							SUM(o.tp_cas) [TP], 
							SUM(o.naklad) [NA],
							SUM((o.ta_cas * a.pocet + o.tp_cas * a.pocet/a.davka)/60) [TC] 
						FROM operace o 
						LEFT JOIN typy_operaci p ON o.id_typy_operaci=p.id
						LEFT JOIN druhy_operaci d ON p.id_druhy_operaci=d.id
						LEFT JOIN vazby v ON o.id=v.id_operace 
						LEFT JOIN (SELECT id_produkty, id_nabidky, vyrobni_davka [davka], mnozstvi [pocet], ROW_NUMBER () OVER (PARTITION BY [id_produkty] 
									ORDER BY id_nabidky, id_produkty, mnozstvi DESC) rn FROM pocty
									) AS a ON v.id_vyssi = a.id_produkty AND rn=1
						WHERE	a.id_nabidky = $id_nabidky
								AND (o.ta_cas+o.tp_cas)*100>0
						GROUP BY 
							d.poradi,
							CASE WHEN d.zkratka = 'Strojní' THEN d.zkratka + ' - ' + p.nazev ELSE d.zkratka END
						ORDER BY d.poradi
				")->fetchAll();
	}
	
	/**
	 * Sumarizuje potřebu kapacit za celou nabídku
	 * @param type $id_nabidky
	 * @return type
	 */
	public function sumKapacitaNab($id_nabidky)
	{
		return $this->CONN->query("	
					SELECT	
							d.poradi [poradi],
							d.id [idd], 
							CASE WHEN d.zkratka = 'Strojní' THEN d.zkratka + ' - ' + p.nazev ELSE d.nazev END [druh],
							SUM(o.ta_cas) [TA], 
							SUM(o.tp_cas) [TP], 
							SUM(o.naklad) [NA],
							SUM((o.ta_cas * COALESCE(h.mnozstvi,a.pocet) + o.tp_cas * COALESCE(h.mnozstvi,a.pocet)/COALESCE(h.vyrobni_davka,a.davka,1))/60) [TC] 
						FROM operace o 
						LEFT JOIN typy_operaci p ON o.id_typy_operaci=p.id
						LEFT JOIN druhy_operaci d ON p.id_druhy_operaci=d.id
						LEFT JOIN vazby v ON o.id=v.id_operace 
						LEFT JOIN (SELECT id_produkty, id_nabidky, vyrobni_davka [davka], mnozstvi [pocet], ROW_NUMBER () OVER (PARTITION BY [id_produkty] 
									ORDER BY id_nabidky, id_produkty, mnozstvi DESC) rn FROM pocty
									) AS a ON v.id_vyssi = a.id_produkty AND rn=1
						LEFT JOIN ceny c ON v.id_vyssi = c.id_produkty AND c.aktivni = 1 AND c.id_typy_cen=7 AND a.id_nabidky = c.id_nabidky
						LEFT JOIN pocty h ON c.id_pocty = h.id
						WHERE	a.id_nabidky = $id_nabidky
								AND (o.ta_cas+o.tp_cas)*100>0
						GROUP BY 
							d.poradi,
							d.id, 
							CASE WHEN d.zkratka = 'Strojní' THEN d.zkratka + ' - ' + p.nazev ELSE d.nazev END,
							LEFT(d.zkratka,1)
						ORDER BY LEFT(d.zkratka,1), d.poradi
				")->fetchAll();
	}	
	
	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		return $this->CONN->dataSource("SELECT o.*, tp.nazev [typ], tp.ta_min, tp.ta_rezerva,
										COALESCE(sb.nazev,'') [nsablona], COALESCE(tt.nazev,'') [npostup]
									FROM operace o
                                        LEFT JOIN typy_operaci	tp ON o.id_typy_operaci=tp.id
										LEFT JOIN tp_sablony	sb ON o.id_sablony = sb.id
										LEFT JOIN tpostupy		tt ON o.id_tpostup = tt.id
                                    WHERE o.id = $id");
	}

	
	/**
	 * 	Vrací vybrané sloupce pro hromadné zadávání operací
	 * @return record set
	 */
	
	
	public function getTypesOper($id_produktu, $id_tpostup=0, $id_sablony=0, $desmist = 4)
	{
		if($id_produktu>0){
			if($id_tpostup == 0 || $id_sablony == 0){
				return $this->CONN->query("SELECT tp.id [idto]
									, tp.zkratka [ztyp]
									, tp.nazev [typ]
									, tp.poradi [poradi]
									, op.poradi [oporadi]
									, do.zkratka [zkratka]
									, COALESCE(op.id, 0) [ido]
									, COALESCE(op.popis, tp.nazev) [popis]
									, tp.nazev [tnazev]
									, COALESCE(ROUND(op.ta_cas,$desmist), 0) [ta_cas]
									, COALESCE(ROUND(op.tp_cas,$desmist), 0) [tp_cas]
									, COALESCE(ROUND(op.naklad,$desmist), 0) [naklad]
									, ao.atr_ks
									, op.id_sablony
									, op.id_tpostup
								FROM typy_operaci tp
								LEFT JOIN 
									(SELECT * FROM operace o 
										LEFT JOIN vazby va ON o.id=va.id_operace
										WHERE va.id_vyssi = $id_produktu) op
									ON tp.id=op.id_typy_operaci
								LEFT JOIN druhy_operaci do ON tp.id_druhy_operaci=do.id
								LEFT JOIN 
									(SELECT id_typy_operaci, COUNT(id) [atr_ks] FROM atr_typy_oper
											GROUP BY id_typy_operaci
									) ao
									 ON tp.id = ao.id_typy_operaci
								ORDER BY tp.poradi			
							")->fetchAll();
			} else {
				return $this->CONN->query("SELECT tp.id [idto]
									, tp.zkratka [ztyp]
									, tp.nazev [typ]
									, tp.poradi [poradi]
									, do.zkratka [zkratka]
									, COALESCE(op.id, 0) [ido]
									, COALESCE(op.popis, tp.nazev) [popis]
									, COALESCE(ROUND(op.ta_cas,$desmist), 0) [ta_cas]
									, COALESCE(ROUND(op.tp_cas,$desmist), 0) [tp_cas]
									, COALESCE(ROUND(op.naklad,$desmist), 0) [naklad]
									, ao.atr_ks
									, op.id_sablony
									, op.id_tpostup
									, tp.npostup
									, tp.nsablona
								FROM 
									(SELECT sat.id_typy_operaci [id], sat.poradi, sat.nazev, sat.id_tp_sablony, tyo.ta_min, tyo.ta_rezerva, tyo.tp_default, 
											tyo.id_druhy_operaci, tyo.zkratka, tpo.nazev [npostup], sab.nazev [nsablona]
										FROM tp_sablony_typy sat
											LEFT JOIN typy_operaci tyo ON sat.id_typy_operaci = tyo.id
											LEFT JOIN tpostupy_sablony tsa ON sat.id_tp_sablony = tsa.id_sablony
											LEFT JOIN tpostupy tpo ON tsa.id_tpostup = tpo.id
											LEFT JOIN tp_sablony sab ON tsa.id_sablony = sab.id
										WHERE tpo.id_produkty = $id_produktu AND tsa.id_tpostup = $id_tpostup AND tsa.id_sablony = $id_sablony		
									) tp
								LEFT JOIN 
									(SELECT * FROM operace o 
										LEFT JOIN vazby va ON o.id=va.id_operace
										WHERE va.id_vyssi = $id_produktu) op
									ON tp.id=op.id_typy_operaci
								LEFT JOIN druhy_operaci do ON tp.id_druhy_operaci=do.id
								LEFT JOIN 
									(SELECT id_typy_operaci, COUNT(id) [atr_ks] FROM atr_typy_oper
											GROUP BY id_typy_operaci
									) ao
									 ON tp.id = ao.id_typy_operaci
								ORDER BY tp.poradi			
							")->fetchAll();
			}
		} else {
			return false;
		}
	}

	public function getIdTypesOper()
	{
		return $this->CONN->fetchPairs("SELECT tp.id, tp.nazev
									FROM typy_operaci tp 
									LEFT JOIN druhy_operaci d ON tp.id_druhy_operaci=d.id
									ORDER BY d.zkratka DESC
									");
		 
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
	public function insertSimple($data = array())
	{
		return $this->CONN->insert($this->table, $data)->execute(dibi::IDENTIFIER);
	}

	/**
	 * Insert / update table operace
	 * @param type $data
	 * @param type $id_produkty
	 * @param type $id_operace
	 * @return type
	 */
	public function insert($data = array(), $id_produkty = 0, $id_operace = 0)
	{
		$p = array();
		if($id_operace==0){
			$ido = $this->CONN->insert($this->table, $data)->execute(dibi::IDENTIFIER);
			$p[0] = 1;
			if($id_produkty>0){
				$datav = array('id_vyssi' => $id_produkty, 'id_operace' => $ido);
				$this->CONN->insert('vazby', $datav)->execute();
			}
		} else {
			$ido = $id_operace;
			$this->update($ido, $data);
			$p[0] = 2;
		}
		$p[1] = $ido;
		return $p;
	}
	
	
	/**
	 * Deletes 1 record [or each assignet to product in table vazby] in the table
	 * @param int
	 * @return mixed
	 */
	public function delete($id = 0, $id_produkt = 0)
	{
		// delete one operation
		if($id>0){
			return $this->CONN->delete($this->table)->where('id=%i', $id)->execute();
		}
		// delete all product operations
		if($id==0 && $id_produkt>0){
			return $this->CONN->query("DELETE FROM operace 
									WHERE id IN 
									(SELECT id_operace FROM vazby WHERE id_vyssi=$id_produkt 
											AND id_operace is not null)
								");
		}
		// delete plonk operations
		if($id<0 && $id_produkt>0){
			return $this->CONN->query("DELETE FROM operace 
									WHERE id IN 
									(SELECT id_operace FROM vazby WHERE id_vyssi=$id_produkt 
											AND id_operace is not null)
									AND id_sablony is null AND id_tpostup is null
								");
		}
		
	}
	
	/**
	 * Prepare data from group form into array for insert/update
	 * @param type $formdata
	 * @param type $id_postup
	 * @param type $id_sablony
	 * @return int
	 */
	public function prepGroupOperData($formdata = array(), $id_postup = 0, $id_sablony = 0)
	{
		if($id_postup>0 and $id_sablony>0){
			$maxj = 11;
		} else {
			$maxj = 10;
		}
			$rows  = (array) $formdata;
			$gdata = array();
			$idata = array();
			$i = 0;
			$p = 0;
			$r = 0;
			$j = 0;
			$popis = '';
			$ta = 0;
			$tp = 0;
			$na = 0;
			$pop = '';
			$tac = 0;
			$tpc = 0;
			$nak = 0;
			$idto = 0;
			$ido = 0;
			$poradi = '';
			foreach($rows as $k => $v ){
				$j++;
				switch($j){
					case 1:
						$popis = $v;
					case 2:
						$ta = floatval($v);
					case 3:
						$tp = floatval($v);
					case 4:
						$na = floatval($v);
					case 5:
						$pop = $v;
					case 6:
						$tac = floatval($v);
					case 7:
						$tpc = floatval($v);
					case 8:
						$nak = floatval($v);
					case 9:
						$idto = intval($v);
					case 10:
						$ido = intval($v);
					case 11:
						$poradi = $v;						
				}
				if($j == $maxj) {
					if( $popis <> $pop or $ta<>$tac or $tp<>$tpc or $na<>$nak ){
						$p++;
						$idata[$p]['ido'] = $ido;
						$gdata[$p]['popis'] = $popis;
						$gdata[$p]['ta_cas'] = $ta;
						$gdata[$p]['tp_cas'] = $tp;
						$gdata[$p]['naklad'] = $na;
						$gdata[$p]['id_typy_operaci'] = $idto;
						if($maxj==11){
							$gdata[$p]['id_tpostup'] = $id_postup;
							$gdata[$p]['id_sablony'] = $id_sablony;
						}
						$r++;
					}
					$j = 0;
					$popis = '';
					$ta = 0;
					$tp = 0;
					$na = 0;
					$pop = '';
					$tac = 0;
					$tpc = 0;
					$nak = 0;
					$idto = 0;
					$ido = 0;
					$poradi = '';
				}
			}
		$ret = array();
		$ret['gdata'] = $gdata;
		$ret['idata'] = $idata;
		$ret['r'] = $r;
		return $ret;
	}
		
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insUpdGroupOper($data = array(), $idata = array(), $id_product=0, $pocet=0)
	{
		$c = array();
		$c['i'] = 0;
		$c['u'] = 0;
		$c['T'] = 0;
		if($id_product>0){
			for($i=1; $i<=$pocet; $i++){
				$opdata = $data[$i];
				if($opdata){
					$kusdata['id_vyssi'] = $id_product;
					$r = $this->insert($opdata, $id_product, $idata[$i]['ido']);
					$id_operace = $r[1];
					if($kusdata && $id_operace){
						$kusdata['id_operace'] = $id_operace;
						$this->insertVazby($kusdata);
						if ($r){$c['T']++;}
						if ($r[0]==1){$c['i']++;}
						if ($r[0]==2){$c['u']++;}
					}
					unset($kusdata);
				}
				unset($opdata);
			}
		}
		return $c;
	}

	/**
	 * vloží vazbu na operaci, pokud již neexistuje
	 * @param type $data 
	 */
	public function insertVazby($data = array())
	{
		if(!$this->findVazba($data['id_vyssi'], $data['id_operace'])){
			$this->CONN->insert('vazby', $data)->execute();
		}
	}

	/**
	 * testuje, zda vazba na operaci již existuje
	 * @param type $idv
	 * @param type $ido
	 * @return type 
	 */
	private function findVazba($idv, $ido)
	{
		$cnt = count($this->CONN->select("*")->from("vazby")->where("id_vyssi=$idv AND id_operace=$ido"));
		return $cnt>0;
	}
	
	/**
	 * Zobrazí atributy
	 * @param type $id_operace
	 * @param type $id_produkt
	 * @return type 
	 */
	public function showCalcOper($id_operace, $id_produkt){
	
		return $this->CONN->query("	
								SELECT a.*, COALESCE(ROUND(ao.mnozstvi,2),0) [mnozstvi]
										, ao.cas_min
										, COALESCE(ao.id, 0) [idao]
										FROM
											(SELECT	op.id [ido]
													, at.id_typy_operaci [idt]
													, at.id_atr_casu [ida]
													, ac.zkratka
													, ac.nazev
													, ac.typ
													, ROUND(ac.cas_sec,4) [cas_sec]
												FROM [DEMS].[dbo].atr_typy_oper at
												LEFT JOIN [DEMS].[dbo].atr_casu ac ON at.id_atr_casu = ac.id
												LEFT JOIN [DEMS].[dbo].operace op ON at.id_typy_operaci = op.id_typy_operaci
												WHERE 
													op.id = $id_operace) a
											LEFT JOIN [DEMS].[dbo].atr_operaci ao ON a.ida = ao.id_atr_casu 
														AND a.ido = ao.id_operace 
											WHERE ao.id_produktu = $id_produkt OR ao.id_produktu is null
											ORDER BY ida, typ  
								");
	}

	
	
	/**
	 * Inserts data to the assign table
	 * @param array
	 * @return Identifier
	 */
	public function insertTC($data = array())
	{
		return $this->CONN->insert('atr_operaci', $data)->execute();
	}
	

	/**
	 * Deletes records in atr_operace by id_oper
	 * @param int
	 * @return mixed
	 */
	public function deleteTC($id_oper)
	{
		return $this->CONN->delete('atr_operaci')->where('id_operace=%i', $id_oper)->execute();
	}
	
	/**
	 * Updates data in the table
	 * @params int, array
	 * @return mixed
	 */
	public function updateTC($id, $data = array())
	{
		return $this->CONN->update('atr_operaci', $data)->where('id=%i', $id)->execute();
	}

	
	public function insertTcalc($data = array(), $id_atr_oper = 0)
	{
		$p = array();
		if($id_atr_oper == 0){
			$id_atr_oper = $this->CONN->insert('atr_operaci', $data)->execute(dibi::IDENTIFIER);
			$p[0] = 1;
		} else {
			$this->updateTC($id_atr_oper, $data);
			$p[0] = 2;
		}
		$p[1] = $id_atr_oper;
		return $p;
	}
	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insUpdTcalc($data = array(), $idata = array(), $id_operace, $pocet = 0)
	{
		$c = array();
		$c['i'] = 0;
		$c['u'] = 0;
		$c['T'] = 0;
		if ($id_operace > 0){
			for($i = 1; $i <= $pocet; $i++){
				$adata = $data[$i];
				if($adata){
					$r = $this->insertTcalc($adata, $idata[$i]['idao']);
					if ($r){$c['T']++;}
					if ($r[0]==1){$c['i']++;}
					if ($r[0]==2){$c['u']++;}
				}
				unset($adata);
			}
		}
		return $c;
	}
	
	
	
}


