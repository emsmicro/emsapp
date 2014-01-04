<?php

use Nette\Object;
/**
 * Model Typy Operaci class
 */

class Stroj extends Model // DibiRow obstará korektní načtení dat
{
	/**
	 *  @var string
	 * @table
	 */
	private $table = 'stroje';
	

    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }

	/**
	 * 	Vrací vybrané sloupce
	 * @return record set
	 */
	public function show()
	{
		return $this->CONN->select('*')->from($this->table);
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
	public function update($id, $data = array(), $param = array())
	{
		$data = $this->calculate($data, $param);
		return $this->CONN->update($this->table, $data)->where('id=%i', $id)->execute();
	}
	
	/**
	 * Inserts data to the table
	 * @param array
	 * @return Identifier
	 */
	public function insert($data = array(), $param = array())
	{
		$data = $this->calculate($data, $param);
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
	 * Vypočítá parametry hodnot ceny hodinove sazby stroje
	 * @param type $data
	 */
	private function calculate($data = array(), $param = array())
	{
			$data['sazba_instalace']	= $data['sazba_instalace']/100;
			$data['vytizeni']			= $data['vytizeni']/100;
			$data['vyuziti_prikonu']	= $data['vyuziti_prikonu']/100;
			$data['naklady_udrzba']		= $data['naklady_udrzba']/100;
			$data['naklady_ostatni']	= $data['naklady_ostatni']/100;
			if ($data['stari']==""){$data['stari']=0;}
			if ($data['spotreba_dusiku']==""){$data['spotreba_dusiku']=0;}
			$data['kapacita']		= $param['stroj_kapcita_sm'] * $data['smennost'] * $data['vytizeni'];
			
			$investice = $data['poriz_cena'] * (1 + $data['sazba_instalace']);
			$cenapenez = ($investice * $param['urokova_mira']/100)/2;
			$naklploch = $data['plocha'] * $param['naklady_plochy'];
					
			$data['odpisy_hod']		= $investice / $data['doba_odpisu'] / $data['kapacita'];
			$data['naklady_fixni']	= $data['odpisy_hod'] + ($cenapenez + $naklploch) / $data['kapacita'];
			
			$elektrina	= $data['prikon'] * $data['vyuziti_prikonu'] * $param['cena_elekriny'];
			$dusik		= $data['spotreba_dusiku'] * $param['cena_dusiku'];
			$varnakost	= ($data['naklady_udrzba'] + $data['naklady_ostatni']) * $investice / $data['kapacita'];
		
			$data['naklady_variabilni'] = $elektrina + $dusik + $varnakost;
			
			$data['hodinova_cena'] = ceil($data['naklady_fixni'] + $data['naklady_variabilni']);
			
			return $data;
	}
}


