<?php

use Nette\Object;
/**
 * Model Set sazeb operaci class
 */

class SetTarifu extends Model
{
	/**
	 *  @var string
	 * @table
	 */
	private $table = 'set_tarifu';
	

    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * Vrací obsah tabulky podle platnosti_od sestupně 
	 * @return record set
	 */
	
	public function show()
	{
		return $this->CONN->select('*')->from($this->table)->orderBy('platnost_od','DESC');

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
		$data = $this->calculate($data);
		return $this->CONN->update($this->table, $data)->where('id=%i', $id)->execute();
	}
	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insert($data = array())
	{
		$data = $this->calculate($data);
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

	
	private function calculate($data = array())
	{
		foreach ($data as $key => $value) {
			if(!strrpos("nazev,platnost_od,platnost_do", $key)){
				if($value==""){$data[$key]=0;}
			}
		}
		$data['fond_rucni'] = ($data['dny_pracovni']-$data['dny_dovolena']-$data['dny_svatky']-$data['dny_nemoc']);
		$data['fond_strojni'] = ($data['dny_pracovni']-$data['dny_svatky']-$data['dny_odstavky']);
		return $data;
	}
	
	/**
	 * Počítá hodinovou nákladovou sazbu v dané tarifní třídě
	 * @param type $tarify
	 * @param type $param
	 * @return type
	 */
	public function calcSazba($tarify = array(), $param = array())
	{
		if($tarify){
			foreach($tarify as $t){
				//roční náklady										//ruční fond = prac dny bez dovolené, svátků, nemocí
				$ntarif = $param['fond_rucni']*8*$t->tarif;			//tarifni mzda
				$npripl = $ntarif*$param['priplatky']/100;			//připlatky za přečasy, směny apod.
				$nbonus = 12 * $param['doch_bonus'];				//docházkový bonus
				$ndovol = $param['dny_dovolena']
							/ $param['fond_rucni']*($ntarif+$npripl+$nbonus)
							+ $param['dny_dovolena']*8*$param['navyseni_prumeru'];	//náhrada za dovolenou
				$nsvate = $param['dny_svatky']
							/ $param['fond_rucni']*($ntarif+$npripl+$nbonus)
							+ $param['dny_svatky']*8*$param['navyseni_prumeru'];	//náhrada za svátky
				$nodmen = $param['odmeny']*$ntarif/12;				//roční odměny
				$ncelkm = $ntarif+$npripl+$nbonus+$ndovol+$nsvate;	//celkové roční příjmy (bez připoj. a stravného)
				
				$nobedy = $param['fond_rucni']*$param['stravne'];	//příspěvek na obědy
				$npojis = $ncelkm*$param['penzijni_poj']/100;		//penz. připoj. z vyplacené mzdy bez náhrad obědů
				$nodvod = ($ncelkm)*$param['odvody']/100;	//odvody ze všeho i s obědy, ne penz.poj.
				$sumrok	= $ncelkm + $npojis + $nodvod + $nobedy;	//celkové roční náklady na pracovníka
				
				//skutečný roční hodinový fond s přesčasy
				$hodrok = $param['fond_rucni']*(1+$param['podil_prescasu']/100)*8;
				//skutečná hodinová sazba kalkulovaná
				$hsazba = $sumrok/$hodrok;
				$t->calc = round($hsazba,1);
				
				$tarodv = $t->tarif*(1+$param['odvody']/100);
				$procnt = $tarodv>0 ? (1-$tarodv/$t->calc)*100 : 0;
				$procta = $t->calc>0 ? ($t->tarif/$t->calc)*100 : 0;
				$t->perc = $procnt;
				$procik = (float) $procnt;
				$procib = 100-$procik-$procta;
				$proodv = $t->calc>0 ? ($tarodv-$t->tarif)/$t->calc*100 : 0;
				$navyse = $t->calc*$procik/100;
				$odvodh = $t->tarif*$param['odvody']/100;
				//grafické znázornění
				$k = 1.3;
				$pta = $procta * $k;
				$pik = $procik * $k;
				$pib = $procib * $k;
				$hig = 10;
				$t->graf = "<span title='Tarif ".round($t->tarif,1)." Kč/h, tj. ".round($procta,1)." %' style='display:inline-block; background-color:#65AEF8; width:$pta"."px; height:$hig"."px; vertical-align: middle; box-shadow:2px 2px 2px #BBB;'>&nbsp;</span>"
						  ."<span title='Odvody ".round($odvodh,2)." Kč/h, tj. ".round($proodv,1)." %' style='display:inline-block; background-color:#DDD; width:$pib"."px; height:$hig"."px; vertical-align: middle; box-shadow:2px 2px 2px #BBB;'>&nbsp;</span>"
						  ."<span title='Navýšení ".round($navyse,2)." Kč/h'style='display:inline-block; background-color:#FFB111; width:$pik"."px; height:$hig"."px; vertical-align: middle; box-shadow:2px 2px 2px #BBB;'>&nbsp;</span>";
			}
		}
		return $tarify;
	}	
	
	/**
	 * Připraví data pro update tarifů
	 * @param type $tarify
	 * @return type
	 */
	private function tarifyProUpdate($tarify) {
		$data = array();
		$j=0;
		foreach($tarify as $tarif){
			$data[$j]['id'] = $tarif->tid;
			$data[$j]['hodnota'] = $tarif->calc;
			$j++;
		}
		return $data;
	}

		
	/**
	 * Aktualizuje tarify pro kalkulaci předvypočtenými hodnotami
	 * @param type $id = id_set_tarifu
	 * @param type $data
	 */
	public function updateTarify($id, $tarify) {
		$data = $this->tarifyProUpdate($tarify);
		$tar = new Tarif;
		$udato = array();
		$this->CONN->begin();
		$ret = TRUE;
		try {
			foreach($data as $dato){
				$id_tarif = $dato['id'];
				$upd['hodnota'] = $dato['hodnota'];
				$res = $tar->update($id_tarif, $upd);
				$ret = $ret and $res;
			}
			$this->CONN->commit();
		} catch (DibiException $e) {
			$this->CONN->rollback();
			throw new Nette\Application\BadRequestException("Aktualizace sazeb tarifnů se nezdařila (Rollback transaction.)");
			return FALSE;
		}
		return $ret;
	}
	
	
	
}


