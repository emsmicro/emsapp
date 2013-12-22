<?php

use Nette\Object, 	
	Nette\Caching\Cache;

class Import extends Material 
{

	private $table = 'material';
	private $storage;
	private $cache;
	private $upldir;
	
    public function __construct($arr = array())
    {
        parent::__construct($arr);
		$storage = new Nette\Caching\Storages\FileStorage(WWW_DIR.'/../temp/cache');
		$cache = new Cache($storage);
		$this->storage = $storage;
		$this->cache = $cache;
		$this->upldir = UPL_DIR;
    }

	/**
	 * Read data from CSV file into array combine
	 * @param type $filename .. name of file without path
	 * @param type $numrows .. limit of rows, if 0 .. all rows
	 * @param type $isskip1 .. skip first row (if true)
	 * @param type $isconv .. if true, convert CP1250 to UTF-8
	 * @param type $delimiter .. standart delimiter is ; you can set another
	 * @return type array combine
	 */
	public function dataOfCsv($filename, $numrows = 0, $isskip1 = true, $isconv = true, $delimiter=';')
	{
		//Načte data z CSV do pole
			if(!file_exists($this->upldir . '/' . $filename) || !is_readable($this->upldir . '/' . $filename))
				return FALSE;

			$header = NULL;
			$data = array();
			$iscount = ($numrows>0);
			if (($handle = fopen($this->upldir . '/' . $filename, 'r')) !== FALSE)
			{	$i = 0;
				while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
				{
					if(!$header && $isskip1)
						$header = $row;
					else
						$data[] = array_combine($header, $row);
					$i++;
					if($iscount && $i>$numrows){break;}
				}
				fclose($handle);
			}
			if ($isconv){
				$d = array();
				foreach($data as $r => $v){
					foreach($v as $i => $h){
						$d[$r][$i] = iconv("CP1250", "UTF-8//TRANSLIT", $h);
					}
				}
				return $d;
			} else {
				return $data;
			}
	}

	/**
	 * Read data from CSV file into normal array
	 * @param type $filename .. name of file without path
	 * @param type $numrows .. limit of rows, if 0 .. all rows
	 * @param type $isskip1 .. skip first row (if true)
	 * @param type $isconv .. if true, convert CP1250 to UTF-8
	 * @param type $delimiter .. standart delimiter is ; you can set another
	 * @return type array
	 */
	public function arrayOfCsv($filename, $numrows = 0, $isskip1 = true, $isconv = true, $delimiter=';')
	{
		//Načte data z CSV do pole
			if(!file_exists($this->upldir . '/' . $filename) || !is_readable($this->upldir . '/' . $filename))
				return FALSE;

			$header = NULL;
			$data = array();
			$iscount = ($numrows>0);
			if (($handle = fopen($this->upldir . '/' . $filename, 'r')) !== FALSE)
			{	$i = 0;
				while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
				{
					if(!$header && $isskip1)
						$header = $row;
					else
						$data[] = ($row);
					$i++;
					if($iscount && $i>$numrows){break;}
				}
				fclose($handle);
			}
			if ($isconv){
				$d = array();
				foreach($data as $r => $v){
					foreach($v as $i => $h){
						$d[$r][$i] = iconv("CP1250", "UTF-8//TRANSLIT", $h);
					}
				}
				return $d;
			} else {
				return $data;
			}
	}

	
	/**
	 * Read header of CSV file into array
	 * @param type $filename
	 * @param type $isconv
	 * @param type $delimiter
	 * @return array 
	 */
	public function headerOfCsv($filename, $isconv = true, $delimiter=';')
	{
		//Načte záhlaví z CSV do pole
			if(!file_exists($this->upldir . '/' . $filename) || !is_readable($this->upldir . '/' . $filename))
				return FALSE;

			$header = NULL;
			$data = array();
			$i = 0;
			if (($handle = fopen($this->upldir . '/' . $filename, 'r')) !== FALSE)
			{	
				while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
				{
					if(!$header)
						$header = $row;
					else
						$data[] = array_combine($header, $row);
					$i++;
				}
				fclose($handle);
			}
			$this->cache->save('csvfile', $filename);

			//přeindexování pole od 1 a spočítání sloupců
			$h = array();
			$j=0;
			foreach($header as $v){
					$j++;
					$h[$j] = $v;
			}

			if ($isconv){
				$c = array();
				$i = 0;
				foreach($h as $v){
						$i++;
						$c[$i] = iconv("CP1250", "UTF-8//TRANSLIT", $v);
				}
				$this->cache->save('csvhlava', $c);

				return $c;
			}
			$this->cache->save('csvhlava', $h);

			return $h;
	}
	
	/**
	 * Put variable into cache
	 * @param type $key
	 * @param type $somedata 
	 */
	public function toCacheSome($key,$somedata){
		$this->cache->save($key, $somedata);
	}

	/**
	 * Get variable from cache
	 * @param type $key
	 * @return type mixed
	 */
	public function fromCacheSome($key){
		return $this->cache->load($key);
	}

	/**
	 * Get CSV head from cache
	 * @return type array
	 */
	public function fromCacheHead(){
		return $this->cache->load('csvhlava');
	}

	/**
	 * Get CSV filename from cache
	 * @return type 
	 */
	public function fromCacheFile(){
		return $this->cache->load('csvfile');
	}

	/**
	 * Count num of rows in CSV file (without first row = header?)
	 * @param type $filename
	 * @param type $delimiter
	 * @return type int
	 */
	public function rowsOfCsv($filename,$delimiter=';')
	{
			//spočítá řádky CSV (-1)
			if(!file_exists($this->upldir . '/' . $filename) || !is_readable($this->upldir . '/' . $filename))
				return FALSE;

			$data = array();
			$i = -1;
			if (($handle = fopen($this->upldir . '/' . $filename, 'r')) !== FALSE)
			{	
				while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
				{
					$i++;
				}
				fclose($handle);
			}
			return $i;
	}

	/**
	 * Count num of columns in CSV file
	 * @param type $filename
	 * @param type $delimiter
	 * @return int 
	 */
	public function colsOfCsv($filename, $delimiter=';')
	{
			//spočítá sloupce CSV
			if(!file_exists($this->upldir . '/' . $filename) || !is_readable($this->upldir . $filename))
				return FALSE;

			$header = NULL;
			$data = array();
			if (($handle = fopen($this->upldir . '/' . $filename, 'r')) !== FALSE)
			{	
				while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
				{
					if(!$header){
						$header = $row;
						break;
					}
				}
				fclose($handle);
			}
			$j=0;
			foreach($header as $v){
					$j++;
			}
			return $j;
	}

	/**
	 * Save data from CSV into DB table material
	 * @param type $csv
	 * @param type $pairs
	 * @param type $id_product
	 * @param type $isskip1
	 * @return int 
	 */
	
	
	public function goImport($csv, $pairs, $id_product, $isskip1){
		$data = $this->arrayOfCsv($csv, 0, $isskip1);
//		dd($data);
//		dd($pairs);
//		var_dump($data);
//		exit;
		// transakční zpracování
		$this->CONN->begin();
		
		try {		
			
			$this->deleteVazby($id_product);
			//$this->deleteMaterNullMJ();
			$cnt=0;
			foreach($data as $key => $cols){
				$matdata = array();
				$kusdata = array();
				foreach($pairs as $k => $v){
					$dbfield = $k;
					$i = (int) $v;
					$i--;
					$dbvalue = $cols[$i];
					if($dbfield<>'mnozstvi'){

						if($dbfield=='cena_cm'){
							$dbvalue = str_replace(',','.',$dbvalue);
							$dbvalue = str_replace(' ','',$dbvalue);
							$matdata[$dbfield] = (float) $dbvalue;
						} elseif($dbfield=='zkratka') {
	//						$matdata[$dbfield] = '[' . $id_product . '] ' . $dbvalue;
							$matdata[$dbfield] = $dbvalue;
						} else {
							$matdata[$dbfield] = $dbvalue;
						}
					} else {
						$kusdata[$dbfield] = $dbvalue;
					}
				}
				$matdata['zkratka'] = substr($matdata['zkratka'],0,60);   //zkrácení zkratky na 60 znaků
				$matdata['id_meny'] = 1;			//měna je Kč
				$matdata['id_merne_jednotky'] = 1;	//MJ je ks
				$kusdata['id_vyssi'] = $id_product;
				if($matdata){
					$id_material = $this->insertMaterial($matdata);
					if($kusdata && $id_material){
						$kusdata['id_material'] = $id_material;
						$this->insertVazby($kusdata);
						$cnt++;
					}
				}
				unset($matdata);
				unset($kusdata);
			}
			$this->CONN->commit();
			return $cnt;
	
		} catch (DibiException $e) {
			$this->CONN->rollback();
			throw new Nette\Application\BadRequestException("Import dat BOMu se nezdařil (Rollback transaction.)");
			return 0;
		}
		
	}
	
	/**
	 * Insert data into table material
	 * @param type $data
	 * @return type 
	 */
	public function insertMaterial($data = array())
	{
		return $this->CONN->insert($this->table, $data)->execute(dibi::IDENTIFIER);
	}

	/**
	 * Insert data into table vazby
	 * @param type $data 
	 */
	public function insertVazby($data = array())
	{
		$this->CONN->insert('vazby', $data)->execute();
	}

	/**
	 * delete rows from table vazby
	 * @param type $id .. id_material
	 */
	public function deleteVazby($id)
	{
		$this->CONN->delete('vazby')->where('id_material > 0 AND id_vyssi=%i', $id)->execute();
	}

	/**
	 * delete material items from table where id_merne_jednotky is null OR cena_cm is null
	 */
	public function deleteMaterNullMJ()
	{
		$this->CONN->delete($this->table)->where('id_merne_jednotky is null OR cena_cm is null')->execute();
	}

}


