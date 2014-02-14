<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rate
 *
 * @author Mracko
 */
use Nette\Application\UI\Form,
//	Nette\Forms\IControl,
//	Nette\ComponentModel\IContainer,
	Nette\Application\UI\Control;

class Rate extends Control 

{
	
	private $currencies;
	private $des_mist = 2;
	private $dir_kurzy='';
	private $history=TRUE;
	private $source='ECB';
	
	//put your code here
	public function __construct($arr = array('EUR','USD','USD/EUR:3'), $des_mist=2, $dir_kurzy='kurzy', $history=TRUE)
    {
        $this->currencies = $arr;
		$this->des_mist = $des_mist;
		$this->dir_kurzy = $dir_kurzy;
		$this->history = $history;
    }
	
	public function setPath($dir_kurzy)
	{
		$this->dir_kurzy = $dir_kurzy;
	}	

	public function setCurrencies($curr = array())
	{
		$this->currencies = $curr;
	}	

	public function setSource($source)
	{
		$this->source = $source;
	}	
	
	public function setDefaultDecimal($des_mist)
	{
		$this->des_mist = $des_mist;
	}	

	public function setHistoryOFF()
	{
		$this->history = FALSE;
	}	
	
	
	public function getRates()
	{
		return $this->getEcbExchange();
	}	
	
	public function render()
	{
		$this->template->setFile(__DIR__ . '/rates.latte');
		$this->template->exchs = $this->getEcbExchange();
		$this->template->render();
	}	
	
	/**
	 * Vrací kurzy ECB (vztažené k Euru)
	 * @param type $meny .. pole měn: ('EUR:3','USD', 'USD/EUR:4') .. za dvojtečkou počet desetinných míst
	 * @param type $des_mist .. defaultní počet desetiiných míst - za dvojtečkou v poli má přednost
	 * @return array / null
	 */
	public function getEcbExchange() {
		$dirk = WWW_DIR . '\\'. $this->dir_kurzy;
		$XML_file = $this->getXMLfile($dirk, $index = 0);
		if(!$XML_file) {return NULL;}
		$rates1 = $this->getXMLdata($XML_file);
		if($this->history){
			$XML_file2 = $this->getXMLfile($dirk, $index = 1);
			if($XML_file2){
				$rates2 = $this->getXMLdata($XML_file2);
				$rates = $this->compareExchRates($rates1, $rates2);
			}
		} else {
			$rates = $rates1;
		}
		return $rates;
	}
	
	private function compareExchRates($rates1, $rates2) {
		$i = 0;
		foreach ($rates1 as $rat1) {
			$f = $rat1['from'];
			$t = $rat1['to'];
			$r1 = $rat1['rate'];
			foreach ($rates2 as $rat2) {
				if($rat2['from'] == $f and $rat2['to'] == $t){
					$r2 = $rat2['rate'];
					if($r1>$r2){
						$rates1[$i]['devel']='&#8599;';
						$rates1[$i]['devcol']='#2BBD2E'; //green
					}
					if($r1<$r2){
						$rates1[$i]['devel']='&#8601;';
						$rates1[$i]['devcol']='red';
					}
					if($r1==$r2){
						$rates1[$i]['devel']='&#8614;';
						$rates1[$i]['devcol']='#33CCFF'; //blue
					}
				}
			}
			$i++;
		}
		return $rates1;
	}
	
	private function getXMLdata($XML_file) {
		$XML = @simplexml_load_file($XML_file);
		//$XML = @simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");
		//the file is updated daily between 2.15 p.m. and 3.00 p.m. CET
		if(!$XML) {return NULL;}
		try {
			$data = array();
			$data['EUR'] = (float) 1.0;
			$dt = $XML->Cube->Cube;
			$do = $dt->attributes(); 
			$ad = (array) $do;
			$dd = $ad['@attributes'];
			foreach($XML->Cube->Cube->Cube as $r){
				$atts_object = $r->attributes(); //- get all attributes, this is not a real array
				$atts = (array) $atts_object; //- typecast to an array
				$atts = $atts['@attributes'];		
				foreach($atts as $k => $v){
					if($k == 'currency'){$c = $v;}
					if($k == 'rate'){$k = $v;}				
				}
				$data[$c] = (float) $k;
			}
			$date = new DateTime($dd['time']);
			$rates = array();
			$i=0;
			foreach ($this->currencies as $mena) {
				$rates[$i] = $this->getRateCurr($data, $mena, $date);
				$i++;
			}
		} catch (Exception $e) {
			return NULL;
		}
		return $rates;
	}

	private function getRateCurr($data, $curr, $date){
		$des_mist = $this->des_mist;
		$zmena = 1;
		$cmena = 1;
		$rate = array();
		$meny = explode("/", $curr);
		//$mena_from = $meny[0];
		$m1 = explode(":", $meny[0]);
		$mena_from = $m1[0];
		if(isset($m1[1])){
			$des_mist = (int) $m1[1];
		}
		$curr = $m1[0];
		if(isset($meny[1])){
			$m2 = explode(":", $meny[1]);
			$mena_to = $m2[0];
			if(isset($m2[1])){
				$des_mist = (int) $m2[1];
			}
			$curr .= '/'.$m2[0];
		} else {
			$mena_to = $m1[0];
			$mena_from = "CZK";
		}
		foreach ($data as $key => $value) {
			if($key == $mena_to){$zmena = $value;}
			if($key == $mena_from){$cmena = $value;}
		}
		$rate['from'] = $mena_from;
		$rate['to'	] = $mena_to;
		$rate['rate'] = $cmena/$zmena;
		$rate['int'	] = (int) $rate['rate'];
		$rate['dec'	] = round($rate['rate'] - $rate['int'],$des_mist)*pow(10,$des_mist);
		$rate['symbol'] = $this->getSymbolCurr($curr);
		$rate['date'] = $date;
		$rate['url'	] = "http://www.google.com/finance?q=".$rate['to'].$rate['from'];
		$rate['source'] = $this->source;
		$rate['devel'] = '';
		$rate['devcol'] = '';
		return $rate;
	}
	
	private function getSymbolCurr($curr = array()){
		$currencies = explode("/", $curr);
		$symbols="";
		foreach($currencies as $currency){
			switch ($currency) {
				case 'EUR':
					$symb = '&euro;';
					break;
				case 'USD':
					$symb = '$';
					break;
				case 'GBP':
					$symb = '&pound;';
					break;
				case 'JPY':
					$symb = '&yen;';
					break;
				case 'CZK':
					$symb = 'Kč';
					break;
				default:
					$symb = '&curren;';
			}
			$symbols .= $symb ."/";
		}
		if($symbols<>''){
			return substr($symbols,0,-1);
		} else {
			return $symbols;
		}
	}
	
	private function getXMLfile($dir, $index = 0){
		
		$files = array();
		$fullpath = $dir. "/". strtolower($this->source). "_*.xml";
		foreach (glob($fullpath) as $file) {
			$files[] = $file;
		}		
		if ($files){
			rsort($files);
			$c = count($files);
			if($index+1<=$c){
				return $files[$index];
			}
		}
		return FALSE;		
	}


	/**
	 * Vrací aktuální kurz zadané měny
	 * @param type $mena
	 * @return type
	 */
	public function getGoogleExchange($mena, $mena_cil='', $des_mist=2) {
		$castka = 1;
		$mena_zdroj = $mena;
		if($mena_cil==''){
			$mena_cil = 'CZK';
		}
		$symbol = $this->getSymbolCurr($mena);
		$url = 'http://rate-exchange.appspot.com/currency?from='.$mena_zdroj.'&to='.$mena_cil.'&q='.$castka;
		try {
			$json = @file_get_contents($url);
			$data = json_decode($json, true);
		} catch (Exception $e) {
			return null;
		}
		if($data){
			$data['int'] = (int) $data['rate'];
			$data['dec'] = round($data['rate'] - $data['int'],$des_mist)*pow(10,$des_mist);
			$data['symbol'] = $symbol;
			return $data;
		} else {
			return null;
		}
	}
	
	
	
}
