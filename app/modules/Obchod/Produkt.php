<?php

use Nette\Object;
/*
 * Model Produkt class
 */
class Produkt extends Model
{
	/** 
	 * @var string
	 * @table
	 */
	private $table = 'produkty';

	/** 
	 * full query for detailed product data 
	 * @var type string
	 */
	private $full_detail_query = "SELECT 
									COALESCE(n.popis,'.. nepřiřazen ..') [nabidka], 
									COALESCE(n.id,0) [idn], 
									d.vyrobni_davka [davka], 
									d.mnozstvi [ks],
									COALESCE(s.id_stav,0) [id_stav], 
									COALESCE(s.zkratka,'žádný') [stav], 
									s.popis [nstav], 
									s.username, s.uzivatel, s.id_user, s.datzmeny, 
									n.prij_datum [ze_dne],
									p.*, p.id [idp],
									f.id [idf], 
									f.nazev [firma], 
									f.zkratka [zfirma], 
									COALESCE(n.id_set_sazeb,0) [idss], 
									COALESCE(n.id_set_sazeb_o,0) [idsso],
									ss.nazev [sazby], 
									ss.kalkulace [vzorec],
									so.nazev [sazby_o],
									ka.zkratka [kalkzkratka],
									ka.nazev [kalknazev]
									FROM produkty p 
										LEFT JOIN firmy f ON p.id_firmy=f.id
										LEFT JOIN (SELECT DISTINCT id_produkty, id_nabidky FROM ceny) c ON p.id = c.id_produkty 
										LEFT JOIN nabidky n ON c.id_nabidky = n.id 
										LEFT JOIN set_sazeb ss ON n.id_set_sazeb=ss.id
										LEFT JOIN kalkulace ka ON ss.kalkulace=ka.id
										LEFT JOIN set_sazeb_o so ON n.id_set_sazeb_o=so.id
										LEFT JOIN (SELECT id_nabidky, id_produkty, vyrobni_davka, mnozstvi,
											ROW_NUMBER() OVER (PARTITION BY id_nabidky, id_produkty ORDER BY mnozstvi DESC) AS rd
											FROM pocty
											) d ON n.id=d.id_nabidky AND p.id=d.id_produkty AND rd=1
										LEFT JOIN (SELECT sp.id_produkty, sp.datum_zmeny [datzmeny], st.zkratka, st.popis, st.id [id_stav],
													u.username, u.jmeno+' '+u.prijmeni [uzivatel], u.id [id_user],
											ROW_NUMBER() OVER (PARTITION BY sp.id_produkty ORDER BY sp.datum_zmeny DESC) AS rn
											FROM stav_produkt sp
											LEFT JOIN stav st ON sp.id_stav = st.id
											LEFT JOIN users u ON sp.id_user = u.id
											) s ON p.id = s.id_produkty AND rn=1";
		

    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * 
	 *	Vrací obsah tabulky 
	 *  @return record set
	 */

	public function show()
	{
		if($this->filter<>''){
			//$sql_cmd = $this->full_detail_query . "	WHERE Convert(varchar, COALESCE(n.popis,'Přiřadit k nabídce'))+p.zkratka+p.nazev+f.nazev LIKE '%$this->filter%'";
			$uf = new FilterModel;
			$sql_cmd = $this->full_detail_query . "	WHERE " . $uf->setCondFilter("Convert(varchar, COALESCE(n.popis,'Přiřadit k nabídce'))+p.zkratka+p.nazev+f.nazev+Convert(varchar,p.id)", $this->filter);
		} else {
			$sql_cmd = $this->full_detail_query;
		}
		if($this->limit==0 && $this->offset==0){
			return $this->CONN->query($sql_cmd);
		} else {
			$sql_pgs = $this->pagedSql($sql_cmd, '', 'p.id DESC');
			return $this->CONN->query($sql_pgs);
		}
	}
	
	
	/**
	 *
	 * @param type $id_firmy
	 * @param type $id_nabidky
	 *	Vrací atributy tabulky pro konkrétní firmu
	 *  @return record set
	 */
	public function showProduct($id_firmy=0, $id_nabidky=0)
	{	
		$cond="";
		if($id_nabidky==0){
			$cond=" WHERE p.id_firmy=$id_firmy";
		}
		if($id_firmy==0){
			$cond=" WHERE c.id_nabidky=$id_nabidky";
		}
		return $this->CONN->query($this->full_detail_query . $cond);
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
			$cond=" WHERE id_stav=$limitStatus";
		}
		$sql = "$this->full_detail_query WHERE 
						p.id $isIn IN (SELECT id_produkty FROM stav_produkt $cond)";
		return $this->CONN->query( $sql );
	}
	
	/**
	 * 
	 * Vrací detalní data pro konkrétní produkt
	 * @param int
	 * @return record set
	 */		
	public function find($id=0)
	{
		return $this->CONN->query($this->full_detail_query . "	WHERE p.id=$id");
		
	}
	
	
	/**
	 * 
	 * Vrací náklady vybraného produktu
	 * @param int
	 * @return record set
	 */		
	public function costs($id)
	{

		return $this->CONN->query("SELECT tn.poradi, tn.nazev, tn.zkratka, n.hodnota
									FROM naklady n
									LEFT JOIN typy_nakladu tn ON n.id_typy_nakladu=tn.id
								WHERE n.id_produkty=$id
								ORDER BY tn.poradi")->fetchAll();
	}

	/**
	 * 
	 * Vrací ceny vybraného produktu + nabídky
	 * @param int
	 * @return record set
	 */		
	public function prices($id, $id_nabidky, $active_only = TRUE)
	{
		if($id>0 && $id_nabidky>0){
			$activCond = "";
			if ($active_only){$activCond = " AND c.aktivni=1";}
			return $this->CONN->query("SELECT c.id_produkty [id], c.id_nabidky [idn], n.popis [nabidka], c.aktivni, c.id_vzorec, c.id [id_cena],
											t.zkratka, RTRIM(t.zkratka2) [zkratka2], t.id [idt], t.nazev, c.hodnota, c.hodnota_cm, 
											m.zkratka [mena], m.nazev [n_mena], m.id [idm], COALESCE(k.kurz_prodejni,1) [kurz],
											p.id [idpoc], p.vyrobni_davka [davka], p.mnozstvi, t.poradi, 
											v.zkratka [kvzorec], v.nazev [nvzorec], v.popis [pvzorec], ltrim(rtrim(v.definice)) [definice], 
											ltrim(rtrim(v.procedura)) [procedura], ltrim(rtrim(v.param)) [param], ltrim(rtrim(v.mater_c)) [mater_c],
											COALESCE(c.id_set_sazeb,0) [idss], COALESCE(s.nazev,'defaultní') [set_sazeb]
									FROM ceny c
										LEFT JOIN typy_cen	t ON c.id_typy_cen=t.id
										LEFT JOIN meny		m ON c.id_meny=m.id
										LEFT JOIN kurzy		k ON c.id_kurzy=k.id
										LEFT JOIN pocty		p ON c.id_pocty=p.id
										LEFT JOIN nabidky	n ON c.id_nabidky=n.id
										LEFT JOIN kalkulace v ON c.id_vzorec=v.id
										LEFT JOIN set_sazeb s ON c.id_set_sazeb=s.id
								 WHERE c.hodnota>0 AND c.id_produkty=$id AND c.id_nabidky=$id_nabidky AND c.id is not null $activCond
									ORDER BY c.id_produkty, c.aktivni DESC, c.id, m.id, p.id, t.poradi
									")->fetchAll();
		}else{
			return null;
		}
		//return $this->CONN->select('*')->from($this->table)->where('id_produkty=%i', $idp);

	}

	/**
	 * Nastavuje jako aktivní zvolenou cenu
	 * @param type $id
	 * @param type $id_nabidky
	 * @param type $id_cena
	 * @return boolean
	 */
	public function activateProductPrice($id, $id_nabidky, $id_cena)
	{
		if($id_nabidky>0 && $id>0 && $id_cena>0){
			$d1 = $this->CONN->query("UPDATE ceny SET aktivni = 0
								WHERE	id_nabidky = $id_nabidky AND id_produkty = $id");
			if ($d1) {
				$d2 = $this->CONN->query("UPDATE ceny SET aktivni = 1
									WHERE	id_nabidky = $id_nabidky 
											AND id_produkty = $id
											AND id = $id_cena");
				return $d2;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
		
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
		// výmaz cen, tpostupů, kusovníků a operací produktu
		$rdel = $this->CONN->delete('ceny')->where('id_produkty=%i', $id)->execute();
		$mat = new Material;
		$rdel = $mat->delete(0, $id);
		$ope = new Operace;
		$rdel = $ope->delete(0, $id);
		$rdel = $this->CONN->delete('tpostupy')->where('id_produkty=%i', $id)->execute();
		$rdel = $this->CONN->delete($this->table)->where('id=%i', $id)->execute();
//		$rdel = $this->CONN->query("DELETE FROM produkty WHERE id=$id");
		return $rdel;
	}

	/**
	 * Asigns product to offer
	 * @param int, int id_product, id_nabidky
	 * @return void
	 */
	public function assign2offer($idp, $idn)
	{
		$result = $this->CONN->query("SELECT count(*) FROM ceny WHERE id_nabidky=$idn AND id_produkty=$idp");
		$cnt = $result->fetchSingle();
		if ($cnt==0)
		{
			// k přiřazení dojde, pakliže už nebylo přiřazení produkt -> nabídka pprovedeno již dříve
			$data = array('id_nabidky' => $idn, 'id_produkty' => $idp, 'id_typy_cen' => 1, 'id_meny' => 1 );
			$this->CONN->insert('ceny', $data)->execute();
		} 
	}

	/**
	 * Asigns product to offer
	 * @param int, int id_product, id_nabidky
	 * @return void
	 */
	public function remove4offer($idp, $idn)
	{
		$this->CONN->delete("ceny")->where("id_nabidky=$idn AND id_produkty=$idp")->execute();
	}
		
	/**
	 * Produkty a ceny nabídky
	 * @param type $id_nabidka
	 * @param type $id_mena
	 * @param type $id_produkt
	 * @return type 
	 */
	public function getOfferPrices($id_nabidka, $aktivni = 1, $id_produkt = 0){
		$cond1 = "";
		$cond2 = "";
		if($id_produkt>0)	{$cond1 = " AND c.id_produkty=$id_produkt ";}
		if($aktivni>0)		{$cond2 = " AND c.aktivni=$aktivni ";}
		return $this->CONN->query("SELECT c.id_nabidky, c.id_produkty, c.id_typy_cen [idtc], t.zkratka, c.hodnota, c.hodnota_cm, c.id_meny, 
								c.id_pocty, n.mnozstvi, n.vyrobni_davka, m.zkratka [mena],
								p.zkratka, p.nazev, t.nazev [nceny], c.aktivni, c.id,
								CASE WHEN c.id_typy_cen=8 THEN c.hodnota*c.aktivni ELSE (c.hodnota*n.mnozstvi)*c.aktivni END [objem]
								FROM ceny c
								LEFT JOIN produkty p ON c.id_produkty=p.id
								LEFT JOIN typy_cen t ON c.id_typy_cen=t.id
								LEFT JOIN pocty n ON c.id_pocty=n.id
								LEFT JOIN meny m ON c.id_meny=m.id
							WHERE c.id_nabidky=$id_nabidka $cond1 $cond2 AND c.id_typy_cen in (8,10)
							ORDER BY c.id_produkty, c.id, t.poradi, c.aktivni DESC")->fetchAll();
	}

		
	/**
	 * Produkty a ceny všech nabídek firmy
	 * @param type $id_nabidka
	 * @param type $id_mena
	 * @param type $id_produkt
	 * @return type 
	 */
	public function getOffersCompany($id_firma, $id_produkt = 0){
		$cond = "";
		if($id_produkt>0){$cond = " AND c.id_produkty=$id_produkt ";}
		return $this->CONN->query("SELECT c.id_nabidky, c.id_produkty, c.id_typy_cen [idtc], 
								tt.zkratka, c.hodnota, c.hodnota_cm, c.id_meny, 
								c.id_pocty, n.mnozstvi, n.vyrobni_davka, m.zkratka [mena],
								p.zkratka, p.nazev, tt.nazev [nceny], v.zkratka [kv], c.aktivni
								FROM ceny c
								LEFT JOIN nabidky o  ON c.id_nabidky=o.id
								LEFT JOIN produkty p ON c.id_produkty=p.id
								LEFT JOIN typy_cen tt ON c.id_typy_cen=tt.id
								LEFT JOIN pocty n ON c.id_pocty=n.id
								LEFT JOIN meny m ON c.id_meny=m.id
								LEFT JOIN kalkulace v ON c.id_vzorec = v.id
							WHERE o.id_firmy=$id_firma $cond  AND c.id_typy_cen in(10,8) --AND c.aktivni>0
							ORDER BY c.id_produkty, c.id_meny, c.id_vzorec, id_pocty, tt.poradi")//->fetchAll();
								->fetchAssoc('id_produkty');
	}
	
	/**
	 *
	 * @param type $id_nabidka
	 * @param type $id_produkt
	 * @param type $id_meny
	 * @param type $id_pocty
	 * @return type 
	 */
	public function pricesErase($id_cena)
	{
		if($id_cena>0){
			return $this->CONN->query("DELETE FROM ceny WHERE id=$id_cena");
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param type $id_nabidka
	 * @param type $id_produkt
	 * @param type $id_meny
	 * @param type $id_pocty
	 * @return type 
	 */
	public function getPriceNabOld($id_nabidka, $id_produkt, $id_meny, $id_pocty)
	{
		if($id_nabidka>0 && $id_produkt>0 && $id_meny>0 && $id_pocty>0){
		return $this->CONN->query("SELECT	c.*
									, COALESCE(k.kurz_prodejni,1) [kurz]
									, COALESCE(m.zkratka, 'Kč') [mena] 
									, t.id [tid]
							FROM ceny c
								LEFT JOIN typy_cen t ON c.id_typy_cen=t.id 
								LEFT JOIN kurzy	k ON c.id_kurzy=k.id
								LEFT JOIN meny	m ON c.id_meny=m.id
								WHERE t.zkratka = 'CenaNab'
									AND c.id_nabidky=$id_nabidka AND c.id_produkty=$id_produkt 
									AND c.id_meny=$id_meny AND c.id_pocty=$id_pocty");
		} else {
			return false;
		}
	}

	/**
	 * 
	 * @param type $id
	 * @return boolean
	 */
	public function getPriceNab($id)
	{
		if($id > 0){
		return $this->CONN->query("SELECT	c.*
									, COALESCE(k.kurz_prodejni,1) [kurz]
									, COALESCE(m.zkratka, 'Kč') [mena] 
									, t.id [tid]
							FROM ceny c
								LEFT JOIN typy_cen t ON c.id_typy_cen=t.id 
								LEFT JOIN kurzy	k ON c.id_kurzy=k.id
								LEFT JOIN meny	m ON c.id_meny=m.id
								WHERE t.zkratka = 'CenaNab' AND c.id=$id");
		} else {
			return false;
		}
	}
	

	public function updPriceNab($id, $id_typceny, $hodnota, $hodnota_cm)
	{
		if($id > 0){
		return $this->CONN->query("UPDATE ceny SET hodnota = $hodnota, hodnota_cm = $hodnota_cm
								WHERE id_typy_cen = $id_typceny AND	id = $id");
		} else {
			return false;
		}
	}	
	
	
	
	/**
	 *
	 * @param type $id_nabidka
	 * @param type $id_produkt
	 * @param type $id_meny
	 * @param type $id_pocty
	 * @return type 
	 */
	public function updPriceNabOld($id_nabidka, $id_produkt, $id_meny, $id_pocty, $id_typceny, $hodnota, $hodnota_cm)
	{
		if($id_nabidka>0 && $id_produkt>0 && $id_meny>0 && $id_pocty>0){
		return $this->CONN->query("UPDATE ceny SET hodnota = $hodnota, hodnota_cm = $hodnota_cm
							WHERE	id_typy_cen = $id_typceny
									AND id_nabidky = $id_nabidka AND id_produkty = $id_produkt 
									AND id_meny = $id_meny AND id_pocty = $id_pocty");
		} else {
			return false;
		}
	}	
	
	/**
	 * Insert status of product
	 * @param int int int id_produktu, typ stavu / viz číselník stavy, id_user
	 * @return mixed
	 */
	public function insertProductStatus($id, $status, $id_user)
	{
		if($id==0){return false;}
		//("INSERT id_nabidky, id_stav, datum_zmeny, id_user");
		$result = $this->CONN->query("SELECT count(*) FROM stav_produkt WHERE id_produkty=$id AND id_stav=$status");
		$cnt = $result->fetchSingle();
		if ($cnt>0)
		{
			//mozna update?? co, pokud se vrati stav o krok zpet?? asi delete
			$this->CONN->query("DELETE FROM stav_produkt WHERE id_produkty=$id AND id_stav=$status");
		} 
		if ($status == 21){
			//vymazat případný status "ODEMČENO"
			$this->CONN->query("DELETE FROM stav_produkt WHERE id_produkty=$id AND id_stav=22");
		}
		if ($status == 22){
			//vymazat případný status "UZAMČENO"
			$this->CONN->query("DELETE FROM stav_produkt WHERE id_produkty=$id AND id_stav=21");
		}
		$data = array('id_produkty' => $id, 'id_stav' => $status, 'datum_zmeny' => date("Ymd H:i:s"), 'id_user' => $id_user );
		return $this->CONN->insert('stav_produkt', $data)->execute();

	}
	

	/**
	 * Nastaví hromadně status všem produktům, které patří do zadané nabídky
	 * @param type $id_nabidky
	 * @return type array
	 */
	public function setStatusProdsByOffers($id_nabidky, $status, $id_user)
	{
		$prods = $this->CONN->query("SELECT DISTINCT id_produkty FROM ceny WHERE id_nabidky = $id_nabidky")->fetchAll();
		$i=0;
		foreach ($prods as $prod){
			$res = $this->insertProductStatus($prod['id_produkty'], $status, $id_user);
			if($res){$i++;}
		}		
		return $i>0;
	}
	
	
	/**
	 * Erase status of product
	 * @param int int id_produktu, id_stavu, id_user
	 * @return mixed
	 */
	public function deleteProductStatus($id=0, $status=0, $id_user=0)
	{
		if($iprod>0 && $istat>0 && $iuser>0){
			return	$this->CONN->query("DELETE FROM stav_produkt WHERE id_produkty=$id AND id_stav=$status AND id_user=$id_user");
		}

	}
	
	/**
	 *
	 * @param type $id
	 * @param type $id_meny
	 * @param type $id_pocty
	 * @param type $round
	 * @return type 
	 */

	public function getProdPrice4PieGraph($id, $round = 2){
		$data = $this->getProductPriceDetail($id, $round);
		return $this->dataPairsForGraph($data, 1);
	}

	/**
	 *
	 * @param type $id
	 * @param type $id_meny
	 * @param type $id_pocty
	 * @param type $round
	 * @return type 
	 */
	public function getProdPrice4BarGraph($id, $round = 2){
		$data = $this->getProductPriceDetail($id, $round, 1);
		return $this->dataPairsForGraph($data, 0, 1, $round);
	}
	
	
	/**
	 *	Get prices data from ceny for active price of product
	 * @param type $id
	 * @param type $round
	 * @return type pair data name, value
	 */
	
	public function getProductPriceDetail($id, $round = 2, $desc_order = 0){
		$order = "";
		if($desc_order==1){$order = " ORDER BY tc.id DESC";}
		$data = $this->CONN->query("SELECT RTRIM(tc.zkratka2) [name], ROUND(c.hodnota, $round) [value] 
								FROM ceny c
								LEFT JOIN typy_cen tc ON c.id_typy_cen = tc.id
								WHERE	c.id_produkty = $id 
										AND c.aktivni = 1 
										AND c.id_typy_cen<7  
								$order
							")->fetchAll();
		return $data;
	}
	
	
	/**
	 * Copy product include materials, operations
	 * @param type $id
	 * @param type $id_user
	 * @return type 
	 */
	public function copyProdukt($id, $id_user)
	{
		if($id>0 && $id_user>0){
			$res = $this->CONN->query("
								DECLARE @id_prod int
								EXECUTE copyProduct $id, $id_user, @id_prod OUTPUT
								SELECT @id_prod [p_id]
								")->fetch();
			return $res->p_id;
		} else {
			return false;
		}

	}
	
	/**
	 * Vrací počet vazev materiálu a operací k produktu
	 * @param type $id
	 * @return type
	 */
	public function countProdVazby($id) {
		return $this->CONN->query("SELECT count(id_material) [bom], count(id_operace) [tpv] FROM vazby WHERE id_vyssi=$id")->fetch();
	}
	
	/** 
	 * If product is assigned to offer
	 * @param type $id
	 * @return type boolean
	 */
	public function isAssigned($id) {
		$cnt = $this->CONN->query("select COUNT(*) from ceny WHERE id_produkty = $id")->fetchSingle();
		return $cnt>0;
	}
}


