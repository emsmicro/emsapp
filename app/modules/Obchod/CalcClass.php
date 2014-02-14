<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CalcClass
 *
 * @author Mracko
 */
class CalcClass {
	
	/**
	 * Calculate abolute and relative parameters of price
	 * @param type $id_produkt
	 * @param type $id_nabidka
	 * @param type $id_cena
	 * @param type $zasr_fix = 3% minimální fixní zásobovací režie - NUTNO DOIMPLEMENTOVAT !!!
	 * @param type $vyrr_fix = 32% minimální fixní zásobovací režie - NUTNO DOIMPLEMENTOVAT !!!
	 */
	public $CONN;
	
    private $id_produkt, $id_nabidka, $id_cena, $zasR_fix, $vyrR_fix;
	private $naklady, $ceny, $odpisy;
	
	private $des_mist = 2;
	private $fact_millions = 1000000;	// od jaké výše bude dělena hodnota 10^6 při zobrazení v grafech
	private $fact_thousands = 10000;	// od jaké výše bude dělena hodnota 10^3 při zobrazení v grafech
	
	public $aval = null;
    
    public function __construct($id_produkt, $id_nabidka, $id_cena = 0, $zasR_fix = 0, $vyrR_fix = 0)
    {
		$this->CONN = dibi::getConnection();
        $this->id_produkt = $id_produkt;
		$this->id_nabidka = $id_nabidka;
		$this->id_cena = $id_cena;
		$this->zasR_fix = $zasR_fix;
		$this->vyrR_fix = $vyrR_fix;
        $this->getDataValues();
    }
	
	
	private function getDataValues()
	{
		$kalk = new Kalkul;
		$rows_n			= $kalk->getProductCosts($this->id_produkt);
		$this->naklady	= $kalk->dataIntoObject2D($rows_n, 'zkratka');
		$rows_c			= $kalk->getProductPrices($this->id_produkt, $this->id_nabidka, $this->id_cena);
		$this->ceny		= $kalk->dataIntoObject2keys($rows_c, 'id', 'zkratka');
		$this->odpisy	= $kalk->getOdpisStrojeByProduct($this->id_produkt, $this->id_cena);
		//dd($this->naklady, 'NAKLADY class');
		//dd($rows_c, 'CENY data');
		//dd($this->ceny, 'CENY class');
		//dd($this->odpisy, 'ODPISY class');
		//exit();
		if($this->ceny and $this->naklady){
			$this->aval = $this->getAddedValues();
		}
	}
	
	private function getAddedValues()
	{
		$sets = new SetSazeb;		
		$aval = array();
		$des_mist = $this->des_mist;
		//náklady do proměnných
		$matn = (isset($this->naklady['MaterialN']))  ? round((float) $this->naklady['MaterialN']['hodnota'], $des_mist)*(1+$this->zasR_fix) : 0;
		$matc = (isset($this->naklady['MaterialPC'])) ? round((float) $this->naklady['MaterialPC']['hodnota'], $des_mist) : 0;
		$mata = (isset($this->naklady['MaterialPA'])) ? round((float) $this->naklady['MaterialPA']['hodnota'], $des_mist) : 0;
		$rucp = (isset($this->naklady['OperRucPN']))  ? round((float) $this->naklady['OperRucPN']['hodnota'], $des_mist)*(1+$this->vyrR_fix) : 0;
		$rucd = (isset($this->naklady['OperRucDN']))  ? round((float) $this->naklady['OperRucDN']['hodnota'], $des_mist)*(1+$this->vyrR_fix) : 0;
		$monp = (isset($this->naklady['OperMontPN'])) ? round((float) $this->naklady['OperMontPN']['hodnota'], $des_mist)*(1+$this->vyrR_fix) : 0;
		$mond = (isset($this->naklady['OperMontDN'])) ? round((float) $this->naklady['OperMontDN']['hodnota'], $des_mist)*(1+$this->vyrR_fix) : 0;
		$strp = (isset($this->naklady['OperStrPN']))  ? round((float) $this->naklady['OperStrPN']['hodnota'], $des_mist)*(1+$this->vyrR_fix) : 0;
		$strd = (isset($this->naklady['OperStrDN']))  ? round((float) $this->naklady['OperStrDN']['hodnota'], $des_mist)*(1+$this->vyrR_fix) : 0;
		$ostp = (isset($this->naklady['OstatniPN']))  ? round((float) $this->naklady['OstatniPN']['hodnota'], $des_mist) : 0;
		$jedn = (isset($this->naklady['JednorazN']))  ? round((float) $this->naklady['JednorazN']['hodnota'], $des_mist) : 0;
		// ceny do promennych
		$i=0;
		$best = 0;
		$proc = 0;
		foreach ($this->ceny as $cena) {
			$cmat = (isset($cena['MaterialC']))		? round((float) $cena['MaterialC']['hodnota'], $des_mist) : 0;
			$cruc = (isset($cena['RucPraceC']))		? round((float) $cena['RucPraceC']['hodnota'], $des_mist) : 0;
			$cmon = (isset($cena['MontPraceC']))	? round((float) $cena['MontPraceC']['hodnota'], $des_mist) : 0;
			$cstr = (isset($cena['StrPraceC']))		? round((float) $cena['StrPraceC']['hodnota'], $des_mist) : 0;
			$cost = (isset($cena['OstSluzbC']))		? round((float) $cena['OstSluzbC']['hodnota'], $des_mist) : 0;
			$cvyr = (isset($cena['VyrobniC']))		? round((float) $cena['VyrobniC']['hodnota'], $des_mist) : 0;
			$crsp = (isset($cena['SprvRezie']))		? round((float) $cena['SprvRezie']['hodnota'], $des_mist) : 0;
			$czsk = (isset($cena['Zisk']))			? round((float) $cena['Zisk']['hodnota'], $des_mist) : 0;
			$cpro = (isset($cena['ProdCenaP']))		? round((float) $cena['ProdCenaP']['hodnota'], $des_mist) : 0;
			$cnab = (isset($cena['CenaNab']))		? round((float) $cena['CenaNab']['hodnota'], $des_mist) : 0;
			$cjed = (isset($cena['JednorazC']))		? round((float) $cena['JednorazC']['hodnota'], $des_mist) : 0;
			
			foreach ($cena as $c) {
				$ic = $c['id'];
				$cakt = $c['aktivni'];
				$mnoz = (float) $c['mnozstvi'];
				$davk = (float) $c['vyrobni_davka'];
				//zapsani AVAL do pole
				if($davk>0 && $cnab>0) 
				{
					$i++;
					$aval[$ic] = array();
					$sazby = $sets->getSazbyFromSet($c['idss']);
					$aval[$ic]['sazby']		= $sazby;
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
					$aval[$ic]['prace_ks']	= ($rucp + $strp + ($rucd + $strd)/$davk)*(1+$sazby['VyrR']) + ($monp + $mond/$davk)*(1+$sazby['VyrMR']) + $ostp;
					$aval[$ic]['vprir_ks']	= $aval[$ic]['prace_ks'] - ($aval[$ic]['rucni_ks'] + $aval[$ic]['monta_ks'] + $aval[$ic]['stroj_ks'] + $aval[$ic]['ostat_ks']);
					//$aval[$ic]['vprir_ks']['name'] = "Přirážka k výrobním nákladům";
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
					$aval[$ic]['davka_c']	= (($rucd + $strd) * (1+$sazby['VyrR']) + ($mond) * (1+$sazby['VyrMR']))*(1+$sazby['SpraR1']+$sazby['MZisku']);
					// celkem
					$aval[$ic]['maternak']	= $mnoz * $matn;
					$aval[$ic]['rucninak']	= $mnoz * ($rucp + $rucd/$davk);
					$aval[$ic]['montanak']	= $mnoz * ($monp + $mond/$davk);
					$aval[$ic]['strojnak']	= $mnoz * ($strp + $strd/$davk);
					$aval[$ic]['ostatnak']	= $mnoz * $ostp;
					$aval[$ic]['jednonak']	= $jedn;
					$aval[$ic]['vyrobnak']	= $mnoz * $cvyr;
					$aval[$ic]['pracenak']  = $mnoz * $aval[$ic]['prace_ks'];
					$aval[$ic]['vprirazk']	= $mnoz * $aval[$ic]['vprir_ks'];
					$aval[$ic]['vyreznak']	= $mnoz * ($cvyr - ($matn + $rucp + $rucd/$davk + $monp + $mond/$davk + $strp + $strd/$davk + $ostp));
					$aval[$ic]['sluzbnak']	= $mnoz * ($cvyr-$matn);
					$aval[$ic]['trzba']		= $mnoz * $cnab;
					$aval[$ic]['trzbamat']	= $mnoz * $cmat;
					$aval[$ic]['trzbajed']	= $cjed;
					$aval[$ic]['kalkzisk']	= $mnoz * $czsk;
					$aval[$ic]['spravrez']	= $mnoz * $crsp;
					$marmat = $mnoz * ($cmat - $matn);
					$matrnak = $marmat < $mnoz*$matn*($sazby['ZasR']) ? $marmat : $mnoz*$matn*($sazby['ZasR']);
					$aval[$ic]['avalkalk']	= $aval[$ic]['trzba'] - $aval[$ic]['pracenak'] - $aval[$ic]['maternak'] - $matrnak;
					$aval[$ic]['avalcist']	= $aval[$ic]['trzba'] + $aval[$ic]['trzbajed']
												- $aval[$ic]['maternak'] 
												- $aval[$ic]['strojnak'] 
												- $aval[$ic]['rucninak']
												- $aval[$ic]['montanak']
												- $aval[$ic]['ostatnak']
												- $aval[$ic]['jednonak'];
					$aval[$ic]['avalcis2']	= $aval[$ic]['trzba'] + $aval[$ic]['trzbajed'] - $aval[$ic]['pracenak'] - $aval[$ic]['maternak'] - $aval[$ic]['jednonak'];
					$aval[$ic]['odpisnak']	= (float) $this->odpisy['odpis'];
					$aval[$ic]['stronnak']	= (float) $this->odpisy['naklad'];
					$aval[$ic]['strojcas']	= (float) $this->odpisy['cas'];
					$matnproc = $matn/$cnab * 100;
					$sluzproc = ($cvyr-$matn)/$cnab * 100;
					$vyreproc = ($cvyr - ($matn + $rucp + $rucd/$davk + $monp + $mond/$davk + $strp + $strd/$davk + $ostp))/$cnab * 100;
					$sprvproc = $crsp/$cnab * 100;
					$ziskproc = $czsk/$cnab * 100;
					$odpiproc = $this->odpisy['odpis']/($mnoz * $cnab) * 100;
					$aval[$ic]['matnproc']	= $matnproc;
					$aval[$ic]['matcproc']	= $matn>0 ? ($cmat/$matn - 1) * 100 : 0;
					$aval[$ic]['sluzproc']	= $sluzproc;
					$aval[$ic]['vyreproc']	= $vyreproc;
					$aval[$ic]['sprvproc']	= $sprvproc;
					$aval[$ic]['ziskproc']	= $ziskproc;
					$aval[$ic]['odpiproc']	= $odpiproc;
					$aval[$ic]['avalproc']	= $aval[$ic]['avalkalk']/$aval[$ic]['trzba']*100;
					$aval[$ic]['avalcpr1']	= $aval[$ic]['avalcist']/$aval[$ic]['trzba']*100;
					$aval[$ic]['avalcpr2']	= $aval[$ic]['avalcis2']/$aval[$ic]['trzba']*100;
					$aval[$ic]['avalbest']	= false;
					$aval[$ic]['id_cena']	= $ic;
					$aval[$ic]['c_poradi']	= $i;
					if($aval[$ic]['trzba']>$this->fact_millions){
						$aval[$ic]['factor'] = 6;
					} elseif ($aval[$ic]['trzba']>$this->fact_thousands){
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
				}
			}
		}
		if($best > 0){
			$aval[$best]['avalbest'] = $best;
		}
		return $aval;
	}

	public function getAval() {
		return $this->aval;
	}
	
	
	
}
