<?php

use Nette\Object;
/**
 * Model Set sazeb operaci class
 */

class Postup extends Model
{
	/**
	 *  @var string
	 * @table
	 */
	private $table = 'tpostupy';
	

    public function __construct($arr = array())
    {
        parent::__construct($arr);
    }
	
	/**
	 * 
	 * Vrací obsah tabulky 
	 * param id = id_produkty
	 * @return record set
	 */
	
	public function show($id = 0)
	{
		if ($id > 0) {
			return $this->CONN->select('*')->from($this->table)->where('id_produkty=%i', $id);
		} else {
			return $this->CONN->select('*')->from($this->table);
		}

	}
	
	/**
	 * Vrací data pro konkrétní záznam
	 * @param int
	 * @return record set
	 */
	public function find($id)
	{
		//return $this->CONN->select('*')->from($this->table)->where('id=%i', $id);
		return $this->CONN->query("SELECT tp.*, 
								(SELECT MAX(poradi) FROM tpostupy_sablony
												WHERE id_tpostup = $id) [mporadi]
								FROM tpostupy tp
								WHERE tp.id = $id
								");
	}

	/**
	 * Find assignment of pair TP - Sablona
	 * @param type $id = id_tpostup
	 * @param type $ids = id_sablony
	 * @return type
	 */
	public function findTPSabl($id, $ids)
	{
		return $this->CONN->select('*')->from('tpostupy_sablony')->where("id_tpostup=$id AND id_sablony=$ids");
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
	 * Shows sablony in TP
	 * @param type $id = id_postup
	 * @return type
	 */
	public function showTPSablony($id)
	{
		return $this->CONN->query("SELECT ps.id_tpostup [idtp], ps.id_sablony [ids], 
								tp.zkratka [tzkratka], tp.nazev [tnazev], tp.id_k2 [k2tp], tp.id_produkty, ps.poradi,
								ts.zkratka [szkratka], ts.nazev [snazev],
								pr.zkratka [pzkratka], pr.nazev [pnazev], pr.id_k2 [k2prod]
								FROM tpostupy_sablony ps
								LEFT JOIN tpostupy tp ON ps.id_tpostup = tp.id
								LEFT JOIN tp_sablony ts ON ps.id_sablony = ts.id
								LEFT JOIN produkty pr ON tp.id_produkty = pr.id
								WHERE ps.id_tpostup = $id
								ORDER BY ps.poradi")->fetchAll();
	}

	
	/**
	 * 
	 * 
	 * @param type $id = id_postup
	 * @param type $idsa = id_sablona
	 * @return type
	 */
	public function getOperPostupSablona($id, $idsa = 0, $isAssoc = 1)
	{
		$conds = "";
		$condo = "";
		if ($idsa > 0) {
			$conds = " AND ps.id_sablony = $idsa ";
			$condo = " AND op.id_sablony = $idsa ";
		}
		$res = $this->CONN->query("SELECT 
									  COALESCE(s.poradi, o.oporadi, o.tporadi) [poradi]
									, COALESCE(s.poradi, o.oporadi, o.tporadi)+RIGHT('000' + CAST(COALESCE(s.id_typy_operaci, o.id_typy_operaci) AS VARCHAR(3)), 3) [key]
									, COALESCE(s.id_typy_operaci, o.id_typy_operaci) [id_typy_operaci]
									, COALESCE(s.tnazev, o.tnazev)		[typ]
									, COALESCE(s.tporadi, o.oporadi, o.tporadi)	[tporadi]
									, COALESCE(s.zdruh, o.zdruh)	[zdruh]
									, COALESCE(o.id_operace, 0)		[ido]
									, COALESCE((CASE RTRIM(CAST(o.popis as nvarchar(max))) WHEN '' THEN o.tnazev ELSE o.popis END), s.tnazev, o.tnazev) [popis]
									, COALESCE(ROUND(o.ta_cas,2), 0)	[ta_cas]
									, COALESCE(ROUND(o.tp_cas,2), 0)	[tp_cas]
									, COALESCE(ROUND(o.naklad,2), 0)	[naklad]
									, COALESCE(s.ta_min, o.ta_min)		[ta_min]
									, COALESCE(s.ta_rezerva, o.ta_rezerva)	[ta_rezerva]
									, COALESCE(s.tp_default, o.tp_default)	[tp_default]
									, COALESCE(s.atr_ks, o.atr_ks, 0)		[atr_ks]
									, COALESCE(s.id_sablony, o.id_sablony)	[id_sablony]
									, COALESCE(s.id_tpostup, o.id_tpostup)	[id_tpostup]
									, COALESCE(s.zsablona, o.zsablona)	[zsablona]
									, COALESCE(s.nsablona, o.nsablona)	[nsablona]
									, COALESCE(s.tid_k2, o.tid_k2, 0)	[tid_k2]
									, COALESCE(s.zpostup, o.zpostup)	[zpostup]
									, COALESCE(s.npostup, o.npostup)	[npostup]
									, COALESCE(s.id_produkt, o.id_produkt)	[id_produkt]
									, COALESCE(s.pid_k2, o.pid_k2, 0)	[pid_k2]
									, COALESCE(s.zprodukt, o.zprodukt)	[zprodukt]
									, COALESCE(s.nprodukt, o.nprodukt)	[nprodukt]
									, COALESCE(s.pporadi, 1)	[pporadi]
								FROM
									(SELECT st.id_typy_operaci, ps.id_sablony, ps.id_tpostup, st.poradi [poradi], st.nazev [snazev], ps.poradi [pporadi],  
											tt.nazev [tnazev], tt.poradi [tporadi], tt.ta_min, tt.ta_rezerva, tt.tp_default,
											od.zkratka [zdruh], od.nazev [ndruh], ao.atr_ks,
											sb.zkratka [zsablona], sb.nazev [nsablona],
											tp.id_k2 [tid_k2], tp.zkratka [zpostup], tp.nazev [npostup],
											pr.id [id_produkt], pr.id_k2 [pid_k2], pr.zkratka [zprodukt], pr.nazev [nprodukt]
										FROM tpostupy_sablony	ps
										JOIN tp_sablony			sb ON ps.id_sablony = sb.id
										JOIN tpostupy			tp ON ps.id_tpostup = tp.id
										JOIN tp_sablony_typy	st ON ps.id_sablony = st.id_tp_sablony 
										JOIN typy_operaci		tt ON st.id_typy_operaci = tt.id
										JOIN druhy_operaci		od ON tt.id_druhy_operaci = od.id
										JOIN produkty			pr ON tp.id_produkty = pr.id
										LEFT JOIN (SELECT id_typy_operaci, COUNT(id) [atr_ks] FROM atr_typy_oper
												GROUP BY id_typy_operaci) ao ON tt.id = ao.id_typy_operaci
										WHERE ps.id_tpostup = $id $conds
									) s
								FULL JOIN 
									(SELECT op.id [id_operace], op.id_sablony, op.id_tpostup, op.id_typy_operaci, op.popis [popis], op.poradi [oporadi], op.ta_cas, op.tp_cas, op.naklad,
											tt.nazev [tnazev], tt.poradi [tporadi], tt.ta_min, tt.ta_rezerva, tt.tp_default,
											od.zkratka [zdruh], od.nazev [ndruh], ao.atr_ks,
											sb.zkratka [zsablona], sb.nazev [nsablona],
											tp.id_k2 [tid_k2], tp.zkratka [zpostup], tp.nazev [npostup],
											pr.id [id_produkt], pr.id_k2 [pid_k2], pr.zkratka [zprodukt], pr.nazev [nprodukt]
										FROM operace		op
										JOIN tp_sablony		sb ON op.id_sablony = sb.id
										JOIN tpostupy		tp ON op.id_tpostup = tp.id
										JOIN vazby			va ON op.id = va.id_operace AND tp.id_produkty = va.id_vyssi
										JOIN typy_operaci	tt ON op.id_typy_operaci = tt.id
										JOIN druhy_operaci	od ON tt.id_druhy_operaci = od.id
										JOIN produkty		pr ON tp.id_produkty = pr.id
										LEFT JOIN (SELECT id_typy_operaci, COUNT(id) [atr_ks] FROM atr_typy_oper
												GROUP BY id_typy_operaci) ao ON tt.id = ao.id_typy_operaci
										WHERE op.id_tpostup = $id $condo
									) o ON s.id_sablony = o.id_sablony and s.id_tpostup = o.id_tpostup and s.id_typy_operaci = o.id_typy_operaci
								ORDER BY id_sablony, poradi
		");
		if($isAssoc==1){
			$data = $res->fetchAssoc('id_sablony,=,key');
		} else {
			$data = $res->fetchAll();
		}
		return $data;
	}
	
	
	
	
	
	/**
	 * 
	 * 
	 * @param type $id = id_postup
	 * @param type $idsa = id_sablona
	 * @return type
	 */
	public function getOperPostupSablonaOLDVER($id, $idsa = 0)
	{
		$cond = "";
		if ($idsa > 0) {$cond = " AND ts.id_tp_sablony = $idsa ";}
		$res = $this->CONN->query("SELECT 
									  ts.poradi [poradi]
									, ot.id [id_typ_operace]
									, ot.nazev  [typ]
									, ot.poradi [tporadi]
									, od.zkratka [zkratka]
									, COALESCE(op.id, 0) [ido]
									, COALESCE(op.poradi, ts.poradi) [oporadi]
									, COALESCE(op.popis, ot.nazev, ts.nazev) [popis]
									, COALESCE(ROUND(op.ta_cas,2), 0) [ta_cas]
									, COALESCE(ROUND(op.tp_cas,2), 0) [tp_cas]
									, COALESCE(ROUND(op.naklad,2), 0) [naklad]
									, ao.atr_ks
									, ps.id_sablony
									, ps.id_tpostup
									, ps.poradi [pporadi]
									, sb.id [id_sablona]
									, sb.zkratka [zsablona]
									, sb.nazev [nsablona]
									, tp.id_k2 [tid_k2]
									, tp.id [id_postup]
									, tp.zkratka [zpostup]
									, tp.nazev [npostup]
									, pr.id [id_produkt]
									, pr.id_k2 [pid_k2]
									, pr.zkratka [zprodukt]
									, pr.nazev [nprodukt]

								FROM tp_sablony_typy ts
								LEFT JOIN tp_sablony sb			ON ts.id_tp_sablony = sb.id
								LEFT JOIN tpostupy_sablony ps	ON sb.id = ps.id_sablony
								LEFT JOIN tpostupy tp			ON ps.id_tpostup = tp.id
								LEFT JOIN produkty pr			ON tp.id_produkty = pr.id
								LEFT JOIN typy_operaci ot		ON ts.id_typy_operaci = ot.id
								LEFT JOIN druhy_operaci od		ON ot.id_druhy_operaci = od.id
								FULL JOIN operace op			ON ot.id = op.id_typy_operaci AND tp.id = op.id_tpostup AND ps.id_sablony = op.id_sablony
								LEFT JOIN (SELECT id_typy_operaci, COUNT(id) [atr_ks] FROM atr_typy_oper
											GROUP BY id_typy_operaci) ao ON ot.id = ao.id_typy_operaci
								WHERE tp.id = $id $cond
								ORDER BY ps.poradi, ts.id_tp_sablony, ts.poradi			
			");
		$data = $res->fetchAssoc('id_sablona,=,oporadi');
		return $data;
	}

	/**
	 * Return 1 row operation by sablona
	 * @param type $id .. id_tpostup
	 * @param type $id_sablony
	 * @param type $poradi
	 * @return type
	 */
	public function getPostupSablonaPoradi($id, $id_sablony = 0, $poradi = '')
	{
		$cond = "";
		if ($id_sablony > 0) {$cond = " AND ts.id_tp_sablony = $id_sablony ";}
		if ($poradi != '') {$cond .= " AND ts.poradi = '$poradi' ";}
		$qry = "SELECT 
									  RTRIM(ts.poradi)		[poradi]
									, ot.id			[id_typy_operaci]
									, ot.nazev		[typ]
									, ot.poradi		[tporadi]
									, od.zkratka	[zkratka]
									, ts.poradi		[oporadi]
									, ot.nazev		[popis]
									, ps.id_sablony
									, ps.id_tpostup
									, ps.poradi		[pporadi]
									, sb.id			[id_sablona]
									, sb.zkratka	[zsablona]
									, sb.nazev		[nsablona]
									, tp.id_k2		[tid_k2]
									, tp.id			[id_postup]
									, tp.zkratka	[zpostup]
									, tp.nazev		[npostup]
									, pr.id			[id_produkty]
									, pr.id_k2		[pid_k2]
									, pr.zkratka	[zprodukt]
									, pr.nazev		[nprodukt]

								FROM tp_sablony_typy		ts
								LEFT JOIN tp_sablony		sb	ON ts.id_tp_sablony = sb.id
								LEFT JOIN tpostupy_sablony	ps	ON sb.id = ps.id_sablony
								LEFT JOIN tpostupy			tp	ON ps.id_tpostup = tp.id
								LEFT JOIN produkty			pr	ON tp.id_produkty = pr.id
								LEFT JOIN typy_operaci		ot	ON ts.id_typy_operaci = ot.id
								LEFT JOIN druhy_operaci		od	ON ot.id_druhy_operaci = od.id
								WHERE tp.id = $id $cond
								ORDER BY ps.poradi, ts.id_tp_sablony, ts.poradi			
			";
		$data = $this->CONN->query($qry);
		/*
		dump($qry, $id, $id_sablony, $poradi);
		dump($data);
		dump($data->fetchAll());
		exit;
		 * 
		 */
		return $data;
	}
	
	
	
	public function updateSabl($id, $ids, $data = array())
	{
		return $this->CONN->update('tpostupy_sablony', $data)->where("id_tpostup=$id AND id_sablony=$ids")->execute();
	}
	
	public function insertSabl($data = array())
	{
		return $this->CONN->insert('tpostupy_sablony', $data)->execute();
	}
	
	public function deleteSabl($id, $ids)
	{
		return $this->CONN->delete('tpostupy_sablony')->where("id_tpostup=$id AND id_sablony=$ids")->execute();
	}
	
}


