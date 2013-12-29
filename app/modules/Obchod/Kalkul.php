<?php

use Nette\Object;

/**
 * Model Kalkul class
 * Kalkulace cen produktu
 */
class Kalkul extends Model
{
	/** @var string
	 * @table
	 */
	private $t_mater = 'material';
	private $t_nakl = 'naklady';
	private $t_ceny = 'ceny';

	private $pravidlaString = 'PRAVIDLA';

	public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * Recalculate costs of products
	 * @param int, int id_product, id_set_sazeb_operací
	 * @return void
	 */
	public function costsCalc($id, $idsso)
	{
		if($id>0 && $idsso>0){
			return $this->CONN->query("EXECUTE costsCalculate $id, $idsso");
		} else {
			return false;
		}

	}	
	
	/**
	 * Return all prices for product on offer
	 * @param type $id_produkt
	 * @param type $id_nabidka
	 * @param type $id_cena
	 * @return type
	 */
	public function getProductPrices($id_produkt, $id_nabidka, $id_cena = 0){
		$cond1 = "";
		if($id_cena>0)	{$cond1 = " AND ce.id = $id_cena ";}
		return $this->CONN->query("
								SELECT	ce.id, ce.id_nabidky, ce.id_produkty, ce.id_typy_cen, tc.zkratka, ce.aktivni,
										ce.hodnota, ce.hodnota_cm, po.mnozstvi, po.vyrobni_davka, tc.poradi
									FROM $this->t_ceny ce
									LEFT JOIN typy_cen tc ON ce.id_typy_cen = tc.id
									LEFT JOIN pocty po ON ce.id_pocty = po.id
									WHERE	ce.id_nabidky = $id_nabidka 
											AND ce.id_produkty = $id_produkt $cond1
									ORDER BY ce.id, poradi			
			")->fetchAll();
	}
	
	/**
	 * Return all type of product's costs
	 * @param type $id_produkt
	 * @return type
	 */
	public function getProductCosts($id_produkt){
		return $this->CONN->query("
								SELECT na.id, na.id_produkty, na.id_typy_nakladu, tn.zkratka, na.hodnota, tn.poradi
									FROM $this->t_nakl na
									LEFT JOIN typy_nakladu tn ON na.id_typy_nakladu = tn.id
									WHERE na.id_produkty = $id_produkt
									ORDER BY poradi		
			")->fetchAll();
	}
	
	/**
	 * Kalkuluje cenu materiálu pro zadaný produkt v nabídce v BOMu
	 * @param type $id_produktu a id_nabidky
	 * @return type array
	 * 	$ret['pravidlo1'], $ret['pravidlo2']
	 *	$ret['meze1'], $ret['meze2']
	 *	$ret['koef']
	 */
	public function calcMatPrices($id_produktu, $id_nabidky){
		$kmat = $this->getMatCoef($id_nabidky);
		$pravidlo1 = $kmat['przr'];	// zásobovací režie
		$meze1 = $this->parsePravidlo($pravidlo1);
		$this->recalMatPrices($id_produktu, $kmat['koef'], $meze1, FALSE);
		$pravidlo2 = $kmat['prmm']; // materiálová marže
		$meze2 = $this->parsePravidlo($pravidlo2);
		$this->recalMatPrices($id_produktu, $kmat['koef'], $meze2, TRUE);
		$ret = array();
		$ret['pravidlo1']=$pravidlo1;
		$ret['pravidlo2']=$pravidlo2;
		$ret['meze1']=$meze1;
		$ret['meze2']=$meze2;
		$ret['koef']=$kmat['koef'];
		return $ret;
	}
	
	/**
	 * Recaclutate prices of material BOM of product
	 * @param type $idproduktu
	 * @param type $koeficient
	 * @param type $meze - pole s hranicemi nakladu pro stanoveni prirazky
	 * @param type $nacenu - bude se aplikovat prirazka jiz na spoctenou PC? tj. cena_kc3
	 * @return type 
	 */
	public function recalMatPrices($idproduktu, $koeficient, $meze=FALSE, $nacenu=FALSE)
	{
		if($meze & $meze<>''){
			// bude vypočtena alternativní cena
			$sql_cmd = "UPDATE $this->t_mater  
						SET cena_kc2 = cena_kc * $koeficient
						FROM $this->t_mater m
									LEFT JOIN vazby v ON m.id=v.id_material 
									LEFT JOIN meny me ON m.id_meny = me.id
									WHERE v.id_vyssi=$idproduktu";
			$this->CONN->query($sql_cmd);
			
			// cena s mezemi - kalkulační
			if($nacenu){$pole_cena = "cena_kc3";} else {$pole_cena = "cena_kc";}
			$sql_cmd = "UPDATE $this->t_mater SET cena_kc3 = CASE";
			$case = '';
			for($i = 0; $i < count($meze); ++$i) {
				$do = $meze[$i]['mez'];
				$koef = 1 + $meze[$i]['sazba']/100;
				$max = 0;
				if($do>0){
					// menší než
					if($max<$do){$max=$do;}
					$case .= " WHEN cena_kc < $do THEN $pole_cena * $koef";
				} else {
					// větší nebo rovno max
					$case .= " WHEN cena_kc >= $max THEN $pole_cena * $koef";
				}
			}
			$sql_cmd .= $case .	" END
						FROM $this->t_mater m
									LEFT JOIN vazby v ON m.id=v.id_material 
									LEFT JOIN meny me ON m.id_meny = me.id
									WHERE v.id_vyssi=$idproduktu";
		} else {
			// alternativní cena - vynulování
//			$sql_cmd = "UPDATE material  
//						SET cena_kc3 = 0
//						FROM material m
//									LEFT JOIN vazby v ON m.id=v.id_material 
//									LEFT JOIN meny me ON m.id_meny = me.id
//									WHERE v.id_vyssi=$idproduktu";
//			$this->CONN->query($sql_cmd);
			// cena kalkulační
			if($nacenu === FALSE){
				$sql_cmd = "UPDATE $this->t_mater  
							SET cena_kc2 = cena_kc * $koeficient, cena_kc3 = cena_kc * $koeficient
							FROM $this->t_mater m
										LEFT JOIN vazby v ON m.id=v.id_material 
										LEFT JOIN meny me ON m.id_meny = me.id
										WHERE v.id_vyssi=$idproduktu";
			} else {
				$sql_cmd = "UPDATE $this->t_mater  
							SET cena_kc2 = cena_kc * $koeficient
							FROM $this->t_mater m
										LEFT JOIN vazby v ON m.id=v.id_material 
										LEFT JOIN meny me ON m.id_meny = me.id
										WHERE v.id_vyssi=$idproduktu";
			}
		}
		//dd($sql_cmd,"Sql command");
		return $this->CONN->query($sql_cmd);
	}
		
	/**
	 * Vrací pole s hodnotami koeficinetu ZasR, MatM, vzorec pro cenu materiálu a výsledný koeficient
	 * @param type $idnabidka
	 * @return type array
	 */
	public function getMatCoef($idnabidka){
		$data = $this->CONN->query("SELECT t.zkratka, s.hodnota, k.mater_c, COALESCE(s.pravidlo,'') [pravidlo]
								FROM sazby s
								left join typy_sazeb t ON s.id_typy_sazeb = t.id
								left join set_sazeb ss ON s.id_set_sazeb = ss.id
								left join kalkulace k ON ss.kalkulace = k.id
								left join nabidky n ON ss.id = n.id_set_sazeb
								WHERE n.id = $idnabidka AND t.zkratka IN ('ZasR','MatM')
							")->fetchAll();
		$zasr = 0;
		$matm = 0;
		$vzor = "";
		$koef = 0;
		$przr = "";	// pravidlo u zasobovaci režie
		$prmm = "";	// pravidlo u materiálové marže
		foreach ($data as $d){
			if($d['zkratka']=="ZasR"){
				$zasr = $d['hodnota'];
				$przr = trim($d['pravidlo']);
			}
			if($d['zkratka']=="MatM"){
				$matm = $d['hodnota'];
				$prmm = trim($d['pravidlo']);
			}
			$vzor = $d['mater_c'];
		}
		
		$vzorec = str_replace("MaterialN", "1", $vzor);
		$vzorec = str_replace("ZasR", (string)$zasr, $vzorec);
		$vzorec = str_replace("MatM", (string)$matm, $vzorec);
		if(trim($vzorec)<>''){
			eval("\$koef = $vzorec;");	//vyhodnotí string výraz jako php kód
		} else {
			$koef = 1.50;				// defaultní velká přirážka na materiál, aby to trklo
		}
		$ret['zasr'] = $zasr;
		$ret['matm'] = $matm;
		$ret['vzor'] = $vzorec;
		$ret['koef'] = $koef;
		$ret['przr'] = $przr;
		$ret['prmm'] = $prmm;
		return $ret;
	}

	public function parsePravidlo($pravidlo){
		$ret = array();
		if ($pravidlo==''){
			return false;
		}
		$meze = explode(';', $pravidlo);
		$i = 0;
		foreach ($meze as $m){
			$m = str_replace(' ', '', $m);
			$m = str_replace(',', '.', $m);
			$m = str_replace(')', '', $m);
			$p = explode('(', $m);
			$ret[$i]['mez'] = floatval($p[0]);
			$ret[$i]['sazba'] = floatval($p[1]);
			$i++;
		}
		return $ret;
	}	
	
	/**
	 * Kalkuluje ceny produktu dle definice v kalkulačním vzorci (zadaném nebo aktuálním) 
	 * a aktuálního (nebo zadaného) setu sazeb, vrací veškeré spočítané parametry cen
	 * @param type $id_nabidka
	 * @param type $id_produkt
	 * @param type $id_vzorec
	 * @param type $id_set_sazeb
	 * @param type $id_pocty
	 * @param type $id_meny
	 * @return boolean or array
	 */
	public function kalkulPrices($id_nabidka, $id_produkt, $id_vzorec = 0, $id_set_sazeb = 0, $id_pocty = 0, $id_meny = 1)

	{
		if($id_nabidka==0 or $id_produkt==0){
			throw new Nette\Application\BadRequestException("CHYBA: Výpočet nelze provést, není definován produkt a/nebo nabídka.");
			return FALSE;
		}
		if($id_set_sazeb==0){
			$id_set_sazeb = $this->CONN->fetchSingle("SELECT id_set_sazeb FROM nabidky WHERE id=$id_nabidka");
		}
		if(!$id_set_sazeb){
			throw new Nette\Application\BadRequestException("CHYBA: Výpočet nelze provést, není definován set sazeb režií.");
			return FALSE;
		}
		if($id_vzorec==0){
			$id_vzorec = $this->CONN->fetchSingle("SELECT kalkulace FROM set_sazeb WHERE id=$id_set_sazeb");
		}
		if(!$id_vzorec){
			throw new Nette\Application\BadRequestException("CHYBA: Výpočet nelze provést, není definován kalkulační vzorec.");
			return FALSE;
		}
		if($id_pocty==0){
			$id_pocty = $this->CONN->fetchSingle("SELECT top 1 id FROM pocty WHERE id_nabidky=$id_nabidka AND id_produkty=$id_produkt");
		}
		$id_kurzy	= 1;
		$kurz		= 1.0;
		//$id_meny	= 2; //jen pro test
		if($id_meny>0){
			$kurzy	= $this->CONN->fetchAll("SELECT TOP 1 id, kurz = COALESCE(kurz_prodejni,1.0) FROM kurzy
											WHERE	(platnost_do < '19710101' OR
											platnost_do > GETDATE() OR
											platnost_do IS NULL)
											AND id_meny=$id_meny");
			if($kurzy){
				$id_kurzy = $kurzy[0]['id'];
				$kurz	  = $kurzy[0]['kurz'];
			} else {
				$id_kurzy	= 1;	// CZK
				$kurz		= 1.0;
				$id_meny	= 1;
			}
		}
		
		$vzorec		= $this->CONN->fetchAll("SELECT id, ltrim(rtrim(definice)) [definice], ltrim(rtrim(procedura)) [procedura] FROM kalkulace WHERE id=$id_vzorec");
		
		$tceny		= $this->CONN->fetchAll("SELECT id_typy_cen = id, zkratka [cena], hodnota = 0, hodnota_cm = 0,
										id_nabidky = $id_nabidka, id_produkty = $id_produkt, id_meny = $id_meny,
										id_kurzy = $id_kurzy, id_pocty = $id_pocty, id_vzorec = $id_vzorec, id = 0, aktivni = 0
										FROM typy_cen ORDER BY poradi");
		$tnaklady	= $this->CONN->fetchAll("SELECT tk.id [id], zkratka [naklad], COALESCE(hodnota, 0) [hodnota]
										FROM typy_nakladu tk
										LEFT JOIN naklady nk ON tk.id = nk.id_typy_nakladu AND nk.id_produkty = $id_produkt
										ORDER BY poradi");
		$tsazby		= $this->CONN->fetchAll("SELECT tb.id [id], zkratka [sazba], COALESCE(hodnota, 0) [hodnota], COALESCE(pravidlo,'') [pravidlo], zakladna 
										FROM typy_sazeb tb
										LEFT JOIN sazby sb on tb.id = sb.id_typy_sazeb AND sb.id_set_sazeb = $id_set_sazeb
										ORDER BY poradi");
		$tdavka		= $this->CONN->fetchAll("SELECT id, davka = 'Davka', vyrobni_davka [hodnota] FROM pocty WHERE id=$id_pocty");
		$tmena		= $this->CONN->fetchAll("SELECT id, zkratka [mena] FROM meny WHERE id=$id_meny");
		/*
		$ret['ceny']	= $tceny;
		$ret['naklady'] = $tnaklady;
		$ret['sazby']	= $tsazby;
		$ret['davka']	= $tdavka;
		$ret['mena']	= $tmena;
		$ret['vzorec']	= $vzorec;
		$ret['kurz']	= $kurz;
		*/
		$vzor = $vzorec[0]['definice'];
		$proc = $vzorec[0]['procedura'];
		if($vzor==''){
			if($proc<>''){
				// kalkulace cen dle uložené procedury - STARÝ způsob
				return $this->pricesCalc($id_nabidka, $id_produkt, $id_set_sazeb, $id_meny, $id_pocty, $id_vzorec);
			} else {
				throw new Nette\Application\BadRequestException("CHYBA: Výpočet nelze provést chybí definice vzorce či procedury.");
				return FALSE;
			}
		}
		
		$vzor = $this->replVzorec($vzor, $tnaklady, 'naklad', 'hodnota');
		if($this->isPravidlaUse($vzor)){
			$vzor = $this->replPravidla($vzor, $tsazby, 'sazba', 'hodnota', 'pravidlo', 'zakladna');
		} else {
			$vzor = $this->replVzorec($vzor, $tsazby, 'sazba', 'hodnota');
		}
		$vzor = $this->replVzorec($vzor, $tdavka, 'davka', 'hodnota');
		$vzor = $this->replVzorec($vzor, $tceny, 'cena', 'cena', '$ceny["','"]');
		//$ret['vzor'] = $vzor;
		$ceny = FALSE;
		try {
			eval($vzor);	//vyhodnotí string výraz jako php kód
			if(!isset($ceny['CenaNab']) or $ceny['CenaNab'] == 0){
				$ceny['CenaNab'] = ceil($ceny['ProdCenaP']); // prida vypocet nabidkove ceny
			}
		} catch (Exception $e) {
			$ceny = FALSE;
			throw new Nette\Application\BadRequestException("CHYBA: Nepodařilo se validovat definici vzorce.");
			return FALSE;
		}
		
		$ids = $this->CONN->fetchAll("EXECUTE getIdPrices
									@nabidka_id		= $id_nabidka,
									@product_id		= $id_produkt,
									@id_meny		= $id_meny,
									@id_kurzy		= $id_kurzy,
									@id_pocty		= $id_pocty,
									@id_vzorec		= $id_vzorec,
									@id_setsazeb	= $id_set_sazeb");

		//$ret['IDs'] = $ids;
		if($ceny){
			// doplnime hodnoty do pole a upravime pole
			$tceny = $this->addPriceData($tceny, $ceny, $kurz, $ids, $id_set_sazeb);
			$ret['CENY'] = $ceny;
			// save data into db
			//dd($ret,'PRED');
			$ret = $this->savePrices($tceny, $ids);
			//dd($ret,'PO');
			//exit();
		} else {
			$ret = FALSE;
		}
		return $ret;
	}
	
	/**
	 * Insert prices data into ceny table
	 * @param type $data
	 * @param type $ids
	 * @return type
	 */
	private function savePrices($data, $ids){
		$idCen = $ids[0]['id'];
		$isNew = $ids[0]['isnew']>0;
		$rupd = 0;
		$rins = count($data);
		$this->CONN->begin();
		try {
			// smazeme pouhe prirazeni produktu k nabidce (vznikne nove ulozenim cen)
			$idp = $data[0]['id_produkty'];
			$this->CONN->query("DELETE FROM $this->t_ceny WHERE id_produkty = $idp AND (id is null OR id=0)");
			if(!$isNew and $idCen > 0){
				// odstranime "stare" ceny poku id existuje
				$this->CONN->query("DELETE FROM $this->t_ceny WHERE id=$idCen");
				$rupd = $rins;
				$rins = 0;
			}
			// vlozime nove ceny s novym id
			$this->CONN->query("INSERT INTO $this->t_ceny %ex", $data);
			$this->CONN->commit();
		} catch (DibiException $e) {
			$this->CONN->rollback();
			throw new Nette\Application\BadRequestException("Uložení cen se nezdařilo (Rollback transaction.)");
			return FALSE;
		}
		$r = array();
		$r['id'] = $idCen;
		$r['r_ins'] = $rins;
		$r['r_upd'] = $rupd;
		return $r;
	}
	
	/**
	 * Replace vzorec with values and prefixes
	 * @param type $vzorec
	 * @param type $data
	 * @param type $field
	 * @param type $repl_field
	 * @param type $prefix
	 * @return type string
	 */
	
	private function replVzorec($formula, $data, $field, $repl_field, $prefix='', $suffix='') 
	{
		for($i = 0; $i < count($data); ++$i) {
			$s = '';
			foreach ($data[$i] as $k => $v) {
				if($k == $field){$s = $v;}
				if($k == $repl_field){
					$formula = str_replace($s, $prefix.$v.$suffix, $formula);
					$s = '';
				}
			}
		}
		return $formula;
	}

	private function replPravidla($formula, $data, $field, $repl_field, 
								  $rule_field='', $base_field='', $prefix='', $suffix='') 
	{
		$isRules = ($rule_field <> '' and $base_field <> '');
		
		$debug = 0;
		
		if ($debug>0){
			echo "<div style='width:60%; font-family: Courier; font-size: 14px; margin:10px; padding: 10px;'>";
		}
		foreach ($data as $d) {
			$s = $d[$field];		// s = sazba - zkratka nazvu, ma byt nahrazena hodnotou
			$h = $d[$repl_field];	// h = hodnota - vyse sazby
			if($isRules){
				$b = $d[$base_field];	// b  = zakladna - nazev - test zda neni MATER
				$r = $d[$rule_field];	// r  = pravidlo - hodnota (musi byt prevdeno na cislo)

				if ($debug>0){
					echo "<div style='border: dotted 1px; margin:5px; padding: 5px; color:blue;'>";
					echo "s=[$s], b=[$b], r=[$r]<br />";
					echo "--- vzorec_pred ----------------------------------------------------- <br />"
							.str_replace(';',';<br/>',$formula)."<br />";
					echo "<p style='color:darkgreen'>";
				}

				if ($r<>'' and strtoupper(substr($b,0,5)) <> 'MATER'){
					$r = $this->jakoDesCislo($r);
					if($r==''){$q = $h;} else {$q = $r;}
					$formula = str_replace($s, $prefix.$q.$suffix, $formula);
					if ($debug>0){echo "1: zmeneno = $s => $q ($r)</p>";}
				} else {
					$formula = str_replace($s, $prefix.$h.$suffix, $formula);
					if ($debug>0){echo "2: zmeneno = $s => $h def.</p>";}
				}
				if ($debug>0){
					echo "<p style='color:darkgreen'>"
							. "--- vzorec_po -------------------------------------------------------<br /> "
							.str_replace(';',';<br/>',$formula)."</p>";
					echo "</div>";
				}
			} else {
				$formula = str_replace($s, $prefix.$h.$suffix, $formula);
			}
			if ($debug>0){
				echo "<div style='border: solid 2px darkred; margin:5px; padding: 5px;'>";
				echo "f=$field, s=[$s], h=[$h], b=[$b], r=[$r]<br />";
				echo "--- vzorec_final --------------------------------------------------------<br />"
						.str_replace(';',';<br/>',$formula)."<br />";
				echo "</div>";
			}

		}
		if ($debug>0){
			echo "</div>";
		}

		return $formula;
	}		
		
	/**
	 * If PRAVIDLA is in formula string
	 * @param type $formula
	 * @return boolean
	 */
	private function isPravidlaUse($formula){
		$pos = strrpos(strtoupper($formula), $this->pravidlaString);
		if ($pos === false) { 
			return FALSE;
		} else {
			return TRUE;
		}		
	}

	private function jakoDesCislo($cislo){
		if(trim($cislo)==''){
			return '';
		} else {
			$cislo = str_replace('(', '', $cislo);
			$cislo = str_replace(')', '', $cislo);
			$cislo = str_replace(',', '.', $cislo);
			if(is_numeric($cislo)){
				$c = (float) $cislo;
				$c = $c/100;
				return number_format($c, 6);
			} else {
				return '';
			}
		}
	}	
	
	/**
	 * Insert data into ceny array
	 * @param type $data_into
	 * @param type $data_from
	 * @param type $kurz
	 * @param type $ids
	 * @return type array
	 */
	private function addPriceData($data_into, $data_from, $kurz, $ids, $id_set_sazeb){
		// doplnime hodnoty do pole
		if($data_into){
			for($i = 0; $i < count($data_into); ++$i) {
				foreach ($data_from as $key => $value) {
					if($data_into[$i]['cena'] == $key) {
						$data_into[$i]['hodnota'] = $value;
						$data_into[$i]['hodnota_cm'] = $value/$kurz;
					}
					$data_into[$i]['id'] = $ids[0]['id'];
					$data_into[$i]['aktivni'] = $ids[0]['aktivni'];
					$data_into[$i]['id_set_sazeb'] = $id_set_sazeb;
				}
			}
			// vyradime pole key = cena
			for($i = 0; $i < count($data_into); ++$i) {
				foreach ($data_into[$i] as $key => $value) {
					if($key == 'cena') {unset($data_into[$i][$key]);}
				}
			}
		}		
		return $data_into;
	}	
	
	/**
	 * Recalculate prices of products by currency 
	 * !!! USING STORED PROICEDUREs !!!
	 * @param int, int, int, int, int $id_nabidka, $id_produkt, , $id_set_sazeb, $id_meny, $id_pocty, $id_vzorec
	 * @return void
	 */
	public function pricesCalc($id_nabidka, $id_produkt, $id_set_sazeb, $id_meny, $id_pocty, $id_vzorec = 0)
	{
		if($id_nabidka>0 && $id_produkt>0 && $id_set_sazeb>0 && $id_meny>0 && $id_pocty>0){
			//zjištění dalších parametrů výpočtu
			if ($id_vzorec>0){
				$proc = $this->CONN->query("SELECT id [kid], procedura [procedura], RTRIM(param) [param]
										FROM kalkulace
										WHERE id = $id_vzorec")->fetchAll();
			} else {
				$proc = $this->CONN->query("SELECT COALESCE(k.id,0) [kid], k.procedura [procedura], RTRIM(k.param) [param]
										FROM nabidky n
										LEFT JOIN set_sazeb s ON n.id_set_sazeb=s.id
										LEFT JOIN kalkulace k ON s.kalkulace=k.id
										WHERE n.id = $id_nabidka")->fetchAll();
			}
			if($proc){
				$procedura = $proc[0]['procedura'];
				if ($id_vzorec == 0) {
					$id_vzorec = $proc[0]['kid'];
				}
				$parameter = trim($proc[0]['param']);
				if ($parameter==""){$parameter = "0";}
				$result = $this->CONN->query("
									DECLARE @r_ins int, @r_upd int
									EXECUTE $procedura	
												$id_nabidka, 
												$id_produkt, 
												$id_set_sazeb, 
												$id_meny, 
												$id_pocty,
												$id_vzorec,
												$parameter,
												@r_ins OUTPUT,
												@r_upd OUTPUT
									SELECT @r_ins [r_ins], @r_upd [r_upd]
									")->fetch();
				return $result;
			} else {
				return false;
			}
		} else {
			return false;
		}

	}
	
	/**
	 * Aktualizuje ceny produktu v rámci nabídky
	 * @param type $id = id_cena
	 * @param type $go_where
	 * @return type
	 * 
	 *		$ret['ok']=TRUE/FALSE;
	 *		$ret['message'] = "Text of message"
	 *		$ret['type'] = 'warning','', 'exclamation'
	 *		$ret['redirect'] = PRESENTER:render
	 *		$ret['param'] = $id_nabidky / id_produktu;
	 */
	public function refreshProductPrices($id, $go_where='P')
	{
		$ret = array();		
		
		$data = $this->findPrice($id);
		if ($data){
			$id_nabidka = (int) $data['id_nabidky'];
			$id_produkt = (int) $data['id_produkty'];
			$id_set_sazeb = (int) $data['id_set_sazeb'];
			$id_meny = (int) $data['id_meny'];
			$id_pocty = (int) $data['id_pocty'];
			$id_vzorec = (int) $data['id_vzorec'];
			$id_set_sazeb_o = (int) $data['id_set_sazeb_o'];
			if($go_where=='P'){
				$id_ret = $id_produkt;
			}
			// test existence produktu a nabidky

			if($go_where=='N'){
				$pres = 'Nabidka:';
				$id_ret = $id_nabidka;
				$posret = "";
			} else {
				$pres='Produkt:';
				$id_ret = $id_produkt;
				$posret = "#tceny$id";
			}

			if($id_nabidka==0 or $id_produkt==0){
	//			throw new Nette\Application\BadRequestException("CHYBA: Výpočet nelze provést, není definován produkt a/nebo nabídka.");
				$ret['ok']=FALSE;
				$ret['message'] = "CHYBA: Výpočet nelze provést, není definován produkt a/nebo nabídka.";
				$ret['type'] = 'warning';
				$ret['redirect'] = $pres . "detail" . $posret;
				$ret['param'] = $id_ret;
				return $ret;
			}	
						
			//recalculate BOMs
			$result = $this->calcMatPrices($id_produkt, $id_nabidka);
			if(!$result){
				$ret['ok']=FALSE;
				$ret['message'] = "CHYBA: BOM se nepodařilo zrekalkulovat. Ověřte správnost dat BOMu.";
				$ret['type'] = 'warning';
				$ret['redirect'] = $pres . "detail" . $posret;
				$ret['param'] = $id_ret;
				return $ret;
			}
			
			//recalculate costs
			$result = $this->costsCalc($id_produkt, $id_set_sazeb_o);
			if(!$result){
				$ret['ok']=FALSE;
				$ret['message'] = "CHYBA: Náklady nebyly aktualizovány. Přiřaďte produkt nabídce či prověřte další vstupní data.";
				$ret['type'] = 'warning';
				$ret['redirect'] = $pres . "detail" . $posret;
				$ret['param'] = $id_ret;
				return $ret;
			} else {
				//calculate prices
				$res = $this->kalkulPrices($id_nabidka, $id_produkt, $id_vzorec, $id_set_sazeb, $id_pocty, $id_meny);
				if($res){
					$rins = $res['r_ins'];
					$rupd = $res['r_upd'];
					//nějaký FAKE - hodnoty se z uložené procedury vracejí obráceně - UPD namísto INS???
					$ret['ok']=TRUE;
					$ret['message'] = "Náklady i ceny byly úspěšně aktualizovány (ins/upd = $rins/$rupd).";
					$ret['type'] = '';
				} else {
					$ret['ok']=FALSE;
					$ret['message'] = "Náklady i ceny zřejmě nebyly správně zaktualizovány, pokuste se akci zopakovat.";
					$ret['type'] = 'exclamation';
				}
			}
			$ret['redirect'] = $pres . "detail" . $posret;
			$ret['param'] = $id_ret ;
			return $ret;
		} else {
			$ret['ok']=FALSE;
			$ret['message'] = "Náklady i ceny nebyly zaktualizovány, nepodařilo se získat data o ceně, prodktu, nabídce.";
			$ret['type'] = 'exclamation';
		}
		$ret['redirect'] = $pres . "detail";
		$ret['param'] = $id_ret;
		return $ret;
}
	
	/**
	 * 
	 * Vrací detailní data o cene dle id ceny
	 * @param int
	 * @return record set
	 */		
	public function findPrice($id)
	{
		return $this->CONN->query("
						SELECT 
							c.id_nabidky,
							c.id_produkty,
							n.id_set_sazeb,
							c.id_meny,
							c.id_pocty,
							c.id_vzorec,
							n.id_set_sazeb_o,
							k.param
						FROM ceny c
						LEFT JOIN nabidky n ON c.id_nabidky = n.id
						LEFT JOIN kalkulace k ON c.id_vzorec = k.id
						WHERE c.id=$id
				")->fetch();
	}

	/**
	 * Return all ids offer prices - type = 10
	 * @param type $id = id_nabidky
	 * @return type
	 */
	public function findOfferPrices($id) {
		return $this->CONN->query("SELECT DISTINCT id, id_nabidky, id_produkty, aktivni FROM ceny
										WHERE id_nabidky=$id and id_typy_cen = 10
										ORDER BY id, id_produkty")->fetchAll();
	}
	
	/**
	 * Kalkulace absolutních, jednicových a relativních parametrů operací včšech produktů nabídky
	 * @param type $id_nabidka
	 * @return array or boolean
	 */
	public function calcCapacNab($id_nabidka)
	{
		$prods = $this->CONN->query("SELECT DISTINCT id_produkty [id] FROM ceny WHERE id_nabidky = $id_nabidka")->fetchAll();
		if ($prods) {
			$oper = new Operace();
			$data = array();
			foreach ($prods as $prod) {
				$data[$prod->id]=$oper->sumKapacitaDruh($prod->id, $id_nabidka);
			}
			return $data;
		} else {
			return false;
		}
	}	
	
	/**
	 * Kalkulace absolutních, jednicových a relativních parametrů cen včšech produktů nabídky
	 * @param type $id_nabidka
	 * @return array or boolean
	 */
	public function calcAddValNab($id_nabidka)
	{
		$prods = $this->CONN->query("SELECT DISTINCT id_produkty [id] FROM ceny WHERE id_nabidky = $id_nabidka")->fetchAll();
		if ($prods) {
			$sazba = new Sazba();
			$zasr_fix = $sazba->getRateByType($id_nabidka, "ZasR");
			$vyrr_fix = $sazba->getRateByType($id_nabidka, "VyrR");
			$data = array();
			foreach ($prods as $prod) {
				$data[$prod->id]=$this->calcAddedValue($prod->id, $id_nabidka, $id_cena = 0, $zasr_fix, $vyrr_fix);
			}
			return $data;
		} else {
			return false;
		}
	}	
	
	/**
	 * Calculate abolute and relative parameters of price
	 * @param type $id_produkt
	 * @param type $id_nabidka
	 * @param type $id_cena
	 * @param type $zasr_fix = 3% minimální fixní zásobovací režie - NUTNO DOIMPLEMENTOVAT !!!
	 * @param type $vyrr_fix = 32% minimální fixní zásobovací režie - NUTNO DOIMPLEMENTOVAT !!!
	 * @return type
	 */
	public function calcAddedValue($id_produkt, $id_nabidka, $id_cena = 0, $zasr_fix = 0, $vyrr_fix = 0)
	{
		$des_mist = 2;
		$naklady = $this->getProductCosts($id_produkt);
		$ceny = $this->getProductPrices($id_produkt, $id_nabidka, $id_cena);
		$odps = $this->getOdpisStrojeByProduct($id_produkt, $id_cena);
		$aval = array();
		//náklady do proměnných
		$matn = 0;
		$matc = 0;
		$rucp = 0;
		$rucd = 0;
		$strp = 0;
		$strd = 0;
		$monp = 0;
		$mond = 0;
		$ostp = 0;
		$jedn = 0;
		foreach ($naklady as $naklad) {
			$k = trim($naklad->zkratka);
			switch(true){
				case ($k == 'MaterialN'):
					$matn = round((float) $naklad->hodnota, $des_mist)*(1+$zasr_fix);
					break;
				case ($k == 'MaterialC'):
					$matc = round((float) $naklad->hodnota, $des_mist);
					break;
				case ($k == 'OperRucPN'):
					$rucp = round((float) $naklad->hodnota, $des_mist)*(1+$vyrr_fix);
					break;
				case ($k == 'OperRucDN'):
					$rucd = round((float) $naklad->hodnota, $des_mist)*(1+$vyrr_fix);
					break;
				case ($k == 'OperMontPN'):
					$monp = round((float) $naklad->hodnota, $des_mist)*(1+$vyrr_fix);
					break;
				case ($k == 'OperMontDN'):
					$mond = round((float) $naklad->hodnota, $des_mist)*(1+$vyrr_fix);
					break;
				case ($k == 'OperStrPN'):
					$strp = round((float) $naklad->hodnota, $des_mist)*(1+$vyrr_fix);
					break;
				case ($k == 'OperStrDN'):
					$strd = round((float) $naklad->hodnota, $des_mist)*(1+$vyrr_fix);
					break;
				case ($k == 'OstatniPN'):
					$ostp = round((float) $naklad->hodnota, $des_mist);
					break;
				case ($k == 'JednorazN'):
					$jedn = round((float) $naklad->hodnota, $des_mist);
			}
		}
		// ceny do promennych
		$i=0;
		$ic = 0;
		$cakt = 0;
		$cmat = 0;
		$cruc = 0;
		$cstr = 0;
		$cost = 0;
		$cvyr = 0;
		$crsp = 0;
		$czsk = 0;
		$cpro = 0;
		$cnab = 0;
		$cjed = 0;
		$mnoz = 0;
		$davk = 0;
		$best = 0;
		$proc = 0;
		foreach ($ceny as $cena) {
			if($ic == 0){
				$ic = $cena->id;
				$cakt = $cena->aktivni;
			}
			//zapsani AVAL do pole
			if($cena->id <> $ic && $davk>0 && $cnab>0) 
			{
				$i++;
				$aval[$ic] = array();
				$aval[$ic]['aktivni']	= $cakt;
				$aval[$ic]['mnozstvi']	= $mnoz;
				$aval[$ic]['davka']		= $davk;
				// na kus
				$aval[$ic]['mater_ks']	= $matn;
				$aval[$ic]['rucni_ks']	= ($rucp + $rucd/$davk);
				$aval[$ic]['monta_ks']	= ($monp + $mond/$davk);
				$aval[$ic]['stroj_ks']	= ($strp + $strd/$davk);
				$aval[$ic]['ostat_ks']	= $ostp;
				$aval[$ic]['vyrob_ks']	= $cvyr;
				$aval[$ic]['vyrez_ks']	= $cvyr - ($matn + $rucp + $rucd/$davk + $monp + $mond/$davk + $strp + $strd/$davk + $ostp);
				$aval[$ic]['sluzb_ks']	= ($cvyr-$matn);
				$aval[$ic]['trzba_ks']	= $cnab;
				$aval[$ic]['trmat_ks']	= $cmat;
				$aval[$ic]['jedno_ks']	= $cjed/$davk;
				$aval[$ic]['zisk_ks']	= $czsk;
				$aval[$ic]['sprav_ks']	= $crsp;
				$aval[$ic]['avalk_ks']	= $aval[$ic]['trzba_ks'] - $aval[$ic]['vyrob_ks'] + ($aval[$ic]['trmat_ks'] - $aval[$ic]['mater_ks']);
				$aval[$ic]['avalc_ks']	= $aval[$ic]['trzba_ks'] + $aval[$ic]['jedno_ks'] 
											- $aval[$ic]['mater_ks'] 
											- $aval[$ic]['stroj_ks'] 
											- $aval[$ic]['rucni_ks']
											- $aval[$ic]['monta_ks']
											- $aval[$ic]['ostat_ks']
											- $aval[$ic]['jedno_ks'];

				// celkem
				$aval[$ic]['maternak']	= $mnoz * $matn;
				$aval[$ic]['rucninak']	= $mnoz * ($rucp + $rucd/$davk);
				$aval[$ic]['montanak']	= $mnoz * ($monp + $mond/$davk);
				$aval[$ic]['strojnak']	= $mnoz * ($strp + $strd/$davk);
				$aval[$ic]['ostatnak']	= $mnoz * $ostp;
				$aval[$ic]['jednonak']	= $jedn;
				$aval[$ic]['vyrobnak']	= $mnoz * $cvyr;
				$aval[$ic]['vyreznak']	= $mnoz * ($cvyr - ($matn + $rucp + $rucd/$davk + $monp + $mond/$davk + $strp + $strd/$davk + $ostp));
				$aval[$ic]['sluzbnak']	= $mnoz * ($cvyr-$matn);
				$aval[$ic]['trzba']		= $mnoz * $cnab;
				$aval[$ic]['trzbamat']	= $mnoz * $cmat;
				$aval[$ic]['trzbajed']	= $cjed;
				$aval[$ic]['kalkzisk']	= $mnoz * $czsk;
				$aval[$ic]['spravrez']	= $mnoz * $crsp;
				$aval[$ic]['avalkalk']	= $aval[$ic]['trzba'] - $aval[$ic]['vyrobnak'] + ($aval[$ic]['trzbamat'] - $aval[$ic]['maternak']);
				$aval[$ic]['avalcist']	= $aval[$ic]['trzba'] + $aval[$ic]['trzbajed']
											- $aval[$ic]['maternak'] 
											- $aval[$ic]['strojnak'] 
											- $aval[$ic]['rucninak']
											- $aval[$ic]['montanak']
											- $aval[$ic]['ostatnak']
											- $aval[$ic]['jednonak'];
				$aval[$ic]['odpisnak']	= $odps['odpis'];
				$aval[$ic]['stronnak']	= $odps['naklad'];
				$aval[$ic]['strojcas']	= $odps['cas'];
				$matnproc = $matn/$cnab * 100;
				$sluzproc = ($cvyr-$matn)/$cnab * 100;
				$vyreproc = ($cvyr - ($matn + $rucp + $rucd/$davk + $monp + $mond/$davk + $strp + $strd/$davk + $ostp))/$cnab * 100;
				$sprvproc = $crsp/$cnab * 100;
				$ziskproc = $czsk/$cnab * 100;
				$odpiproc = $odps['odpis']/($mnoz * $cnab) * 100;
				$aval[$ic]['matnproc']	= $matnproc;
				$aval[$ic]['matcproc']	= ($cmat/$matn - 1) * 100;
				$aval[$ic]['sluzproc']	= $sluzproc;
				$aval[$ic]['vyreproc']	= $vyreproc;
				$aval[$ic]['sprvproc']	= $sprvproc;
				$aval[$ic]['ziskproc']	= $ziskproc;
				$aval[$ic]['odpiproc']	= $odpiproc;
				$aval[$ic]['avalproc']	= $aval[$ic]['avalcist']/$aval[$ic]['trzba']*100;
				$aval[$ic]['avalbest']	= false;
				$aval[$ic]['id_cena']	= $ic;
				$aval[$ic]['c_poradi']	= $i;
				if($aval[$ic]['trzba']>1000000){
					$aval[$ic]['factor'] = 6;
				} elseif ($aval[$ic]['trzba']>10000){
					$aval[$ic]['factor'] = 3;
				} else {
					$aval[$ic]['factor'] = 0;
				}
				$aval[$ic]['datagraf']	=  "[['Materiál',".round($matnproc,2)."],['Výr. služby',".round($sluzproc,2)."],['Spr. režie',".round($sprvproc,2)."],['Zisk',".round($ziskproc,2)."]]";

				$Nmater = round($aval[$ic]['maternak'],2);
				$Nstroj = round($aval[$ic]['strojnak'],2);
				$Nruccn = round($aval[$ic]['rucninak']+$aval[$ic]['montanak']+$aval[$ic]['ostatnak'],2);
				$Nvyrez = round($aval[$ic]['vyreznak'],2);
				$Nzarez = round($aval[$ic]['trzbamat']-$aval[$ic]['maternak'],2);
				$Nsprez = round($aval[$ic]['spravrez'],2);
				$Nkzisk = round($aval[$ic]['kalkzisk'],2);
				
				$aval[$ic]['datapie']	=  "
											[
											 {name: 'Zisk', y: $Nkzisk, color: colors[8]},
											 {name: 'Spr. režie', y: $Nsprez, color: colors[7]},
											 {name: 'Výr. režie', y: $Nvyrez, color: colors[1]},
											 {name: 'Strojní N.', y: $Nstroj, color: colors[0]},
											 {name: 'Ruční N.', y: $Nruccn, color: colors[5]},
											 {name: 'Zásob. režie', y: $Nzarez, color: colors[10]},
											 {name: 'Materiál', y: $Nmater, color: colors[6]},
											]
											" ;
				$aval[$ic]['databar']	=  "
											[
											 {name: 'Zisk', data: [$Nkzisk], legendIndex: 6, color: colors[8]},
											 {name: 'Spr. režie', data: [$Nsprez], legendIndex: 5, color: colors[7]},
											 {name: 'Výr. režie', data: [$Nvyrez], legendIndex: 4, color: colors[1]},
											 {name: 'Strojní N.', data: [$Nstroj], legendIndex: 3, color: colors[0]},
											 {name: 'Ruční N.', data: [$Nruccn], legendIndex: 2, color: colors[5]},
											 {name: 'Zásob. režie', data: [$Nzarez], legendIndex: 1, color: colors[10]},
											 {name: 'Materiál', data: [$Nmater], legendIndex: 0, color: colors[6]},
											]
											" ;
				
				
				if($aval[$ic]['avalproc'] > $proc){
					$best = $ic;
					$proc = $aval[$ic]['avalproc'];
				}
				
				$ic = $cena->id;
				$cakt = $cena->aktivni;
				$cmat = 0;
				$cruc = 0;
				$cmon = 0;
				$cstr = 0;
				$cost = 0;
				$cvyr = 0;
				$crsp = 0;
				$czsk = 0;
				$cpro = 0;
				$cnab = 0;
				$cjed = 0;
				$mnoz = 0;
				$davk = 0;
			}
			
			$k = trim($cena->zkratka);
			switch(true){
				case ($k == 'MaterialC'):
					$cmat = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'RucPraceC'):
					$cruc = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'MontPraceC'):
					$cmon = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'StrPraceC'):
					$cstr = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'OstSluzbC'):
					$cost = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'VyrobniC'):
					$cvyr = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'SprvRezie'):
					$crsp = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'Zisk'):
					$czsk = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'ProdCenaP'):
					$cpro = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'CenaNab'):
					$cnab = round((float) $cena->hodnota, $des_mist);
					break;
				case ($k == 'JednorazC'):
					$cjed = round((float) $cena->hodnota, $des_mist);
			}
			$mnoz = (float) $cena->mnozstvi;
			$davk = (float) $cena->vyrobni_davka;
		}

		//zapsani last AVAL do pole
		if($ic > 0 && $davk>0 && $cnab>0) {
			$i++;
			$aval[$ic] = array();
			$aval[$ic]['aktivni']	= $cakt;
			$aval[$ic]['mnozstvi']	= $mnoz;
			$aval[$ic]['davka']		= $davk;
			
			// na kus
			$aval[$ic]['mater_ks']	= $matn;
			$aval[$ic]['rucni_ks']	= ($rucp + $rucd/$davk);
			$aval[$ic]['monta_ks']	= ($monp + $mond/$davk);
			$aval[$ic]['stroj_ks']	= ($strp + $strd/$davk);
			$aval[$ic]['ostat_ks']	= $ostp;
			$aval[$ic]['vyrob_ks']	= $cvyr;
			$aval[$ic]['vyrez_ks']	= $cvyr - ($matn + $rucp + $rucd/$davk + $monp + $mond/$davk + $strp + $strd/$davk + $ostp);
			$aval[$ic]['sluzb_ks']	= ($cvyr-$matn);
			$aval[$ic]['trzba_ks']	= $cnab;
			$aval[$ic]['trmat_ks']	= $cmat;
			$aval[$ic]['jedno_ks']	= $cjed/$davk;
			$aval[$ic]['zisk_ks']	= $czsk;
			$aval[$ic]['sprav_ks']	= $crsp;
			$aval[$ic]['avalk_ks']	= $aval[$ic]['trzba_ks'] - $aval[$ic]['vyrob_ks'] + ($aval[$ic]['trmat_ks'] - $aval[$ic]['mater_ks']);
			$aval[$ic]['avalc_ks']	= $aval[$ic]['trzba_ks'] + $aval[$ic]['jedno_ks'] 
										- $aval[$ic]['mater_ks'] 
										- $aval[$ic]['stroj_ks'] 
										- $aval[$ic]['rucni_ks']
										- $aval[$ic]['monta_ks']
										- $aval[$ic]['ostat_ks']
										- $aval[$ic]['jedno_ks'];
			
			// celkem
			$aval[$ic]['maternak']	= $mnoz * $matn;
			$aval[$ic]['rucninak']	= $mnoz * ($rucp + $rucd/$davk);
			$aval[$ic]['montanak']	= $mnoz * ($monp + $mond/$davk);
			$aval[$ic]['strojnak']	= $mnoz * ($strp + $strd/$davk);
			$aval[$ic]['ostatnak']	= $mnoz * $ostp;
			$aval[$ic]['jednonak']	= $jedn;
			$aval[$ic]['vyrobnak']	= $mnoz * $cvyr;
			$aval[$ic]['vyreznak']	= $mnoz * ($cvyr - ($matn + $rucp + $rucd/$davk + $monp + $mond/$davk + $strp + $strd/$davk + $ostp));
			$aval[$ic]['sluzbnak']	= $mnoz * ($cvyr-$matn);
			$aval[$ic]['trzba']		= $mnoz * $cnab;
			$aval[$ic]['trzbamat']	= $mnoz * $cmat;
			$aval[$ic]['trzbajed']	= $cjed;
			$aval[$ic]['kalkzisk']	= $mnoz * $czsk;
			$aval[$ic]['spravrez']	= $mnoz * $crsp;
			$aval[$ic]['avalkalk']	= $aval[$ic]['trzba'] - $aval[$ic]['vyrobnak'] + ($aval[$ic]['trzbamat'] - $aval[$ic]['maternak']);
			$aval[$ic]['avalcist']	= $aval[$ic]['trzba'] + $aval[$ic]['trzbajed']
										- $aval[$ic]['maternak'] 
										- $aval[$ic]['strojnak'] 
										- $aval[$ic]['rucninak']
										- $aval[$ic]['montanak']
										- $aval[$ic]['ostatnak']
										- $aval[$ic]['jednonak'];
			$aval[$ic]['odpisnak']	= $odps['odpis'];
			$aval[$ic]['stronnak']	= $odps['naklad'];
			$aval[$ic]['strojcas']	= $odps['cas'];
			$matnproc = $matn/$cnab * 100;
			$sluzproc = ($cvyr-$matn)/$cnab * 100;
			$vyreproc = ($cvyr - ($matn + $rucp + $rucd/$davk + $monp + $mond/$davk + $strp + $strd/$davk + $ostp))/$cnab * 100;
			$sprvproc = $crsp/$cnab * 100;
			$ziskproc = $czsk/$cnab * 100;
			$odpiproc = $odps['odpis']/($mnoz * $cnab) * 100;
			$aval[$ic]['matnproc']	= $matnproc;
			$aval[$ic]['matcproc']	= ($cmat/$matn - 1) * 100;
			$aval[$ic]['sluzproc']	= $sluzproc;
			$aval[$ic]['vyreproc']	= $vyreproc;
			$aval[$ic]['sprvproc']	= $sprvproc;
			$aval[$ic]['ziskproc']	= $ziskproc;
			$aval[$ic]['odpiproc']	= $odpiproc;
			$aval[$ic]['avalproc']	= $aval[$ic]['avalcist']/$aval[$ic]['trzba']*100;
			$aval[$ic]['avalbest']	= false;
			$aval[$ic]['id_cena']	= $ic;
			$aval[$ic]['c_poradi']	= $i;
			if($aval[$ic]['trzba']>1000000){
				$aval[$ic]['factor'] = 6;
			} elseif ($aval[$ic]['trzba']>10000){
				$aval[$ic]['factor'] = 3;
			} else {
				$aval[$ic]['factor'] = 0;
			}
			$aval[$ic]['datagraf']	=  "[['Material',".round($matnproc,2)."],['Výr. služby',".round($sluzproc,2)."],['Spr. režie',".round($sprvproc,2)."],['Zisk',".round($ziskproc,2)."]]";

			$Nmater = round($aval[$ic]['maternak'],2);
			$Nstroj = round($aval[$ic]['strojnak'],2);
			$Nruccn = round($aval[$ic]['rucninak']+$aval[$ic]['montanak']+$aval[$ic]['ostatnak'],2);
			$Nvyrez = round($aval[$ic]['vyreznak'],2);
			$Nzarez = round($aval[$ic]['trzbamat']-$aval[$ic]['maternak'],2);
			$Nsprez = round($aval[$ic]['spravrez'],2);
			$Nkzisk = round($aval[$ic]['kalkzisk'],2);

			$aval[$ic]['datapie']	=  "
										[
										 {name: 'Zisk', y: $Nkzisk, color: colors[8]},
										 {name: 'Spr. režie', y: $Nsprez, color: colors[7]},
										 {name: 'Výr. režie', y: $Nvyrez, color: colors[1]},
										 {name: 'Strojní N.', y: $Nstroj, color: colors[0]},
										 {name: 'Ruční N.', y: $Nruccn, color: colors[5]},
										 {name: 'Zásob. režie', y: $Nzarez, color: colors[10]},
										 {name: 'Materiál', y: $Nmater, color: colors[6]},
										]
										" ;
			$aval[$ic]['databar']	=  "
										[
										 {name: 'Zisk', data: [$Nkzisk], legendIndex: 6, color: colors[8]},
										 {name: 'Spr. režie', data: [$Nsprez], legendIndex: 5, color: colors[7]},
										 {name: 'Výr. režie', data: [$Nvyrez], legendIndex: 4, color: colors[1]},
										 {name: 'Strojní N.', data: [$Nstroj], legendIndex: 3, color: colors[0]},
										 {name: 'Ruční N.', data: [$Nruccn], legendIndex: 2, color: colors[5]},
										 {name: 'Zásob. režie', data: [$Nzarez], legendIndex: 1, color: colors[10]},
										 {name: 'Materiál', data: [$Nmater], legendIndex: 0, color: colors[6]},
										]
										" ;
			
			if($aval[$ic]['avalproc'] > $proc){
				$best = $ic;
				$proc = $aval[$ic]['avalproc'];
			}
		}
		if($best > 0){
			$aval[$best]['avalbest'] = $best;
		}
		return $aval;
	}
	
	/**
	 * Vrací id aktivní ceny v poli AVAL
	 * @param type $aval_data
	 * @return type
	 */
	public function getActiveAvalId($aval_data){
		$id = 0;
		foreach($aval_data as $k => $adata){
			if($adata['aktivni']==1){
				$id = $k;
			}
		}
		return $id;
	}
	
	public function sumAddValActiveNab($addv) {
		$data = array();
		$data['maternak'] = 0;
		$data['rucninak'] = 0;
		$data['montanak'] = 0;
		$data['strojnak'] = 0;
		$data['ostatnak'] = 0;
		$data['jednonak'] = 0;
		$data['vyrobnak'] = 0;
		$data['vyreznak'] = 0;
		$data['sluzbnak'] = 0;
		$data['trzba']	  = 0;
		$data['trzbamat'] = 0;
		$data['trzbajed'] = 0;
		$data['kalkzisk'] = 0;
		$data['spravrez'] = 0;
		$data['avalkalk'] = 0;
		$data['avalcist'] = 0;
		$data['odpisnak'] = 0;
		$data['stronnak'] = 0;
		$data['strojcas'] = 0;
		
		foreach($addv as $dk => $dv){
			foreach($dv as $k => $v){
				if($v['aktivni'] == 1){
					$data['maternak'] += $v['maternak'];
					$data['rucninak'] += $v['rucninak'];
					$data['montanak'] += $v['montanak'];
					$data['strojnak'] += $v['strojnak'];
					$data['ostatnak'] += $v['ostatnak'];
					$data['jednonak'] += $v['jednonak'];
					$data['vyrobnak'] += $v['vyrobnak'];
					$data['vyreznak'] += $v['vyreznak'];
					$data['sluzbnak'] += $v['sluzbnak'];
					$data['trzba']	  += $v['trzba'];
					$data['trzbamat'] += $v['trzbamat'];
					$data['trzbajed'] += $v['trzbajed'];
					$data['kalkzisk'] += $v['kalkzisk'];
					$data['spravrez'] += $v['spravrez'];
					$data['avalkalk'] += $v['avalkalk'];
					$data['avalcist'] += $v['avalcist'];
					$data['odpisnak'] += $v['odpisnak'];
					$data['stronnak'] += $v['stronnak'];
					$data['strojcas'] += $v['strojcas'];
				}
			}
		}
		$data['vyrobnin'] = $data['strojnak'] + $data['rucninak'] + $data['montanak'] + $data['ostatnak'];
		$data['rucnicna'] = $data['rucninak'] + $data['montanak'] + $data['ostatnak'];
		$data['zisk_svr'] = $data['kalkzisk'] + $data['spravrez'] + $data['vyreznak'];
		$data['mater_zr'] = $data['trzbamat'];
		$data['zasobrez'] = $data['trzbamat']-$data['maternak'];

		if($data['trzba']>1000000){
			$data['factor'] = 6;
		} elseif ($data['trzba']>10000){
			$data['factor'] = 3;
		} else {
			$data['factor'] = 0;
		}
		
		
		$Nmater = round($data['maternak'],2);
		$Nstroj = round($data['strojnak'],2);
		$Nrucni = round($data['rucninak'],2);
		$Nmonta = round($data['montanak'],2);
		$Nostat = round($data['ostatnak'],2);
		$Nruccn = round($data['rucnicna'],2);
		$Nsluzb = round($data['sluzbnak'],2);
		$Nvyrob = round($data['vyrobnin'],2);
		$Nvyrez = round($data['vyreznak'],2);
		$Nzarez = round($data['zasobrez'],2);
		$Nsprez = round($data['spravrez'],2);
		$Nkzisk = round($data['kalkzisk'],2);
		$Nzisvr = round($data['zisk_svr'],2);
		$Ntrmat = round($data['trzbamat'],2);
		$Ntrzba = round($data['trzba'],2);
		$Nodpis = round($data['odpisnak'],2);
		
		$data['cenagraf']	=  "
								[
								 {name: 'Zisk', data: [$Nkzisk], legendIndex: 3, color: colors[8]},
								 {name: 'Spr. režie', data: [$Nsprez], legendIndex: 2, color: colors[7]},
								 {name: 'Výr. služby', data: [$Nsluzb], legendIndex: 1, color: colors[0]},
								 {name: 'Materiál + ZR', data: [$Ntrmat], legendIndex: 0, color: colors[6]},
								]
								" ;
		$data['naklgraf']	=  "
								[
								 {name: 'Zisk', data: [$Nkzisk], legendIndex: 6, color: colors[8]},
								 {name: 'Spr. režie', data: [$Nsprez], legendIndex: 5, color: colors[7]},
								 {name: 'Výr. režie', data: [$Nvyrez], legendIndex: 4, color: colors[1]},
								 {name: 'Strojní N.', data: [$Nstroj], legendIndex: 2, color: colors[0]},
								 {name: 'Celk. ruční N.', data: [$Nruccn], legendIndex: 1, color: colors[5]},
								 {name: 'Zásob. režie', data: [$Nzarez], legendIndex: 3, color: colors[10]},
								 {name: 'Materiál', data: [$Nmater], legendIndex: 0, color: colors[6]},
								]
								" ;
		$data['naklpie']	=  "
								[
								 {name: 'Zisk', y: $Nkzisk, color: colors[8]},
								 {name: 'Spr. režie', y: $Nsprez, color: colors[7]},
								 {name: 'Výr. režie', y: $Nvyrez, color: colors[1]},
								 {name: 'Strojní N.', y: $Nstroj, color: colors[0]},
								 {name: 'Celk. ruční N.', y: $Nruccn, color: colors[5]},
								 {name: 'Zásob. režie', y: $Nzarez, color: colors[10]},
								 {name: 'Materiál', y: $Nmater, color: colors[6]},
								]
								" ;
		$data['naklcatg']	=  "['Materiál', 'Výrobní služby', 'Zisk a přidaná hodnota']";
		$data['nakldata']	=  "[{
                    y: $Ntrmat,
                    color: colors[6],
                    drilldown: {
                        name: 'Materiál',
                        categories: ['Materiálové náklady', 'Zásobovací režie'],
                        data: [$Nmater, $Nzarez],
                        color: colors[6]
                    }
                }, {
                    y: $Nvyrob,
                    color: colors[0],
                    drilldown: {
                        name: 'Výrobní služby',
                        categories: ['Strojní náklady', '- z toho odpisy', 'Ruční náklady', 'Montážní náklady', 'Ostatní náklady'],
                        data: [$Nstroj, $Nodpis, $Nrucni, $Nmonta, $Nostat],
                        color: colors[0]
                    }
                }, {
                    y: $Nzisvr,
                    color: colors[8],
                    drilldown: {
                        name: 'Celková přidaná hodnota',
                        categories: ['Kalkul. zisk', 'Správní režie', 'Výrobní režie'],
                        data: [$Nkzisk, $Nsprez, $Nvyrez],
                        color: colors[8]
                    }
                }];";
		
		return $data;
	}

	/**
	 * Vrací hodnotu odpisů strojů, nákladů na stroje a strojní čas na produktu u aktivní ceny
	 * s ohledem na mmnožství a dávky u aktivní/zadané ceny
	 * @param type $id_product
	 * @param type $id_cena = 0, pak aktivní, jinak zadaná
	 * @return type
	 */
	public function getOdpisStrojeByProduct($id_product, $id_cena = 0) {
		if($id_cena>0){
			$cond = "c.id = $id_cena";
		} else {
			$cond = "c.aktivni = 1";
		}
		return $this->CONN->query("
			SELECT SUM((o.ta_cas + o.tp_cas/m.vyrobni_davka)*m.mnozstvi/60*s.odpisy_hod) [odpis]
				, SUM((o.ta_cas + o.tp_cas/m.vyrobni_davka)*m.mnozstvi/60*s.hodinova_cena) [naklad]
				, SUM((o.ta_cas + o.tp_cas/m.vyrobni_davka)*m.mnozstvi/60) [cas]
				, p.id, p.zkratka
			FROM produkty p
				LEFT JOIN ceny c ON p.id = c.id_produkty AND c.id_typy_cen = 7 AND $cond
				LEFT JOIN pocty m ON c.id_pocty = m.id
				LEFT JOIN vazby v ON p.id = v.id_vyssi and v.id_operace is not null
				LEFT JOIN operace o ON v.id_operace = o.id
				LEFT JOIN typy_operaci y ON o.id_typy_operaci = y.id
				LEFT JOIN stroje s ON y.id_stroje = s.id
			WHERE p.id = $id_product
			GROUP BY 
				p.id, p.zkratka
			")->fetch();
	}
	
	/**
	 * Vrací hodnotu odpisů strojů, nákladů na stroje a strojní čas na nabídce u aktivní ceny
	 * s ohledem na mmnožství a dávky aktivní ceny
	 * @param type $id_nabidka
	 * @return type
	 */
	public function getOdpisStrojeByOffer($id_nabidka) {
		return $this->CONN->query("
			SELECT SUM((o.ta_cas + o.tp_cas/m.vyrobni_davka)*m.mnozstvi/60*s.odpisy_hod) [odpis]
				, SUM((o.ta_cas + o.tp_cas/m.vyrobni_davka)*m.mnozstvi/60*s.hodinova_cena) [naklad]
				, SUM((o.ta_cas + o.tp_cas/m.vyrobni_davka)*m.mnozstvi/60) [cas]
				, p.id, p.zkratka
				, n.id, Convert(varchar, n.popis) [popis]
			FROM nabidky n 
				LEFT JOIN ceny c ON n.id = c.id_nabidky AND c.id_typy_cen = 7 AND c.aktivni = 1
				LEFT JOIN produkty p ON c.id_produkty = p.id
				LEFT JOIN pocty m ON c.id_pocty = m.id
				LEFT JOIN vazby v ON p.id = v.id_vyssi and v.id_operace is not null
				LEFT JOIN operace o ON v.id_operace = o.id
				LEFT JOIN typy_operaci y ON o.id_typy_operaci = y.id
				LEFT JOIN stroje s ON y.id_stroje = s.id
			WHERE n.id = 63
			GROUP BY 
				n.id, Convert(varchar, n.popis)
			")->fetch();
	}

	
	
	
	
	
	
	}


