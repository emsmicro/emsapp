<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/**
 * Uzivatel presenter
 */

class QueryPresenter extends SpravaPresenter
{

    /** Title constants */
	const TITUL_DEFAULT = 'Informace o databázi';
     /**
	 * @var string
	 * @titul
	 */     
	private $titul;


	public function startup()
	{
		parent::startup();

	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault($dot = '', $idc=0)
	{
		$q = new Query;
		$form = $this['qForm'];
		$jo = FALSE;
		if (!$form->isSubmitted()) {
			if($idc == 9) {
				$r1 = $q->go("SELECT TOP 1 * FROM $dot");
				if($r1){
					$h1 = $q->getHeads($r1);
					$h = '';
					foreach($h1 as $hh){
						if($hh<>'id'){
							$h .= $hh . ", ";
						}
					}
					$h = substr($h, 0, strlen($h)-2);
					$t = "uprav seznam polí a spusť dotaz znovu";
					$jo = TRUE;
				} else {
					$h = '...';
					$t = 'doplň seznam polí místo teček';
				}
				$table = $dot;
				$dot = 
"
-- $t
-- SELECT * FROM $table ORDER BY $h;
WITH numbered
AS ( SELECT *, ROW_NUMBER() OVER ( PARTITION BY $h ORDER BY $h ) AS row_no FROM $table )

-- POZOR!!! Aplikací následujícího příkazu přijdete o data!!!
-- D E L E T E FROM numbered WHERE row_no > 1;

SELECT * FROM numbered WHERE row_no > 1;
";
				$form['dotaz']->value = $dot;
				if(!$jo){$dot = '';}
			} else {
				$form['dotaz']->value = $dot;
			}
		} else {
			$dot = $form['dotaz']->value;
		}
		
		$this->template->sql = $dot;		
		
		$ssql = $dot;
		if($ssql<>'' and
				(strpos(strtoupper($ssql),'UPDATE')>0 
				or strpos(strtoupper($ssql),'INSERT')>0
				or strpos(strtoupper($ssql),'DELETE')>0) ){
			$this->flashMessage('Zadaný dotaz obsahuje UPDATE/INSERT/DELETE, což jsou nepřípustné SQL příkazy.','warning');
		
		} else {
		
			$rows = $q->go($dot);
			$this->template->csql = '';
			if($rows){
				$cnt = $q->countRows($rows);
				if ($cnt<1) {
					$this->flashMessage('Zadaný dotaz "'.$dot.'" nevrátil žádná data.','exclamation');
				} else {
					$head = $q->getHeads($rows);
					$data = $q->getData($rows);
					$this->template->head = $head;
					$this->template->data = $data;
					$this->template->idc = $idc;
					if($idc<9){
						if($idc==1){$this->template->csql = "SELECT * FROM ";}
						if($idc==2){$this->template->csql = "sp_helpdb ";}
						if($idc==4){$this->template->csql = "";}
					} else {
						$this->template->csql = '';
					}
				}
			} else {
				if ($form->isSubmitted()){
					$this->flashMessage('Dotaz nevrátil žádná data.','exclamation');
				}
			}

		}
		$this->template->titul = self::TITUL_DEFAULT;
		$php_server = $_SERVER["REMOTE_ADDR"];
		if(in_array($php_server, array("127.0.0.1","::1")))
		{
			$this->template->server = 'LOCALHOST';
		} else {
			$this->template->server = $php_server . " - " . $_SERVER["HTTP_HOST"];
		}
		//dd($php_server,'SERVER');
	}





	/********************* component factories *********************/



	/**
	 * Item edit form component factory.
	 * @return mixed
	 */
	protected function createComponentQForm()
	{
		$form = new Form;

        $form->addTextArea('dotaz', '', 100, 3)
			->setAttribute('spellcheck','false')
            ->addRule(Form::MAX_LENGTH, 'Dotaz je příliš dlouhý.', 15000);
			
		$form->addSubmit('gou', 'Spustit SQL')->setAttribute('class', 'default');
		$form->addSubmit('dtb', 'Databáze');
		$form->addSubmit('tbs', 'Tabulky');
		$form->addSubmit('sch', 'Schéma');
		$form->addSubmit('srv', 'DB Server');
		$form->addSubmit('inf', 'PhpInfo');
		$form->onSuccess[] = callback($this, 'qFormSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}
	
	public function qFormSubmitted(Form $form)
	{
		
		$id = (int) $this->getParam('id');
		if ($form['gou']->isSubmittedBy()) {
			$data = (array) $form->values;
			$dot = $data['dotaz'];
			$this->redirect('default', $dot);
		}
		
		if ($form['dtb']->isSubmittedBy()) {
			$dot = "SELECT DB_NAME() AS DataBaseName";
			$this->redirect('default', $dot, 2);
		}
		
		if ($form['tbs']->isSubmittedBy()) {
			$dot = "SELECT name, create_date, modify_date, max_column_id_used [cols]
	FROM sys.tables ORDER BY name";
			$dot = "SELECT
obj.name [name], tab.max_column_id_used [cols], ind.rows [rows], tab.create_date, tab.modify_date
FROM sysobjects as obj
INNER JOIN sysindexes as ind on obj.id = ind.id
INNER JOIN sys.tables as tab on obj.id = tab.object_id
WHERE obj.xtype = 'U' AND ind.indid < 2
ORDER BY name";
			$this->redirect('default', $dot, 8);
		}
		
		if ($form['sch']->isSubmittedBy()) {
			$dot = "SELECT table_name [table], ordinal_position [pos], column_name [column], data_type [type], 
	case when character_maximum_length is null then
	case when numeric_scale>0 then
	ltrim(str(numeric_precision))+'('+ ltrim(str(numeric_scale))+')' else
	ltrim(str(numeric_precision)) end else
	ltrim(str(character_maximum_length)) end [length], 
	is_nullable, collation_name [collation] 
FROM information_schema.columns 
WHERE table_name like '%%'
ORDER BY table_name, pos
-- mezi znaky %% doplňte název tabulky spusťte dotaz znovu";
			$this->redirect('default', $dot, 1);
		}
		
		if ($form['srv']->isSubmittedBy()) {
			$dot = "SELECT @@SERVERNAME AS 'Server Name'
,@@VERSION AS 'Server Version'
,@@LANGUAGE AS 'Language'
,@@SERVICENAME AS 'Service'
,SYSTEM_USER AS 'Login'
,USER AS 'User'
";
			$this->redirect('default', $dot, 4);
		}		
		
		if ($form['inf']->isSubmittedBy()) {
			$dot = "";
			$this->redirect('phpInfo');
		}		
		
		
	}
	

}
