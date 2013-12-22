<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/**
 * Uzivatel presenter
 */

class QueryPresenter extends SpravaPresenter
{

    /** Title constants */
	const TITUL_DEFAULT = 'Přímý přístup k datům';
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


		$form = $this['qForm'];
		if (!$form->isSubmitted()) {
			$form['dotaz']->value = $dot;
		} else {
			$dot = $form['dotaz']->value;
		}
		$q = new Query;
		$rows = $q->go($dot);
		$this->template->sql = $dot;
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
				if($idc>0){
					$this->template->csql = "SELECT * FROM ";
				} else {
					$this->template->csql = '';
				}
			}
		} else {
			if ($form->isSubmitted()){
				$this->flashMessage('Dotaz nevrátil žádná data.','exclamation');
			}
		}

		$this->template->titul = self::TITUL_DEFAULT;

	}





	/********************* component factories *********************/



	/**
	 * Item edit form component factory.
	 * @return mixed
	 */
	protected function createComponentQForm()
	{
		$form = new Form;

        $form->addTextArea('dotaz', '', 100, 10)
            ->addRule(Form::MAX_LENGTH, 'Dotaz je příliš dlouhý.', 15000);
			
		$form->addSubmit('gou', 'Spustit SQL')->setAttribute('class', 'default');
		$form->addSubmit('tbs', 'Tabulky');
		$form->addSubmit('sch', 'Schéma');
		$form->addSubmit('srv', 'Server');
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
		if ($form['tbs']->isSubmittedBy()) {
			$dot = "select name, create_date, modify_date, max_column_id_used [cols]
	from sys.tables order by name";
			$dot = "SELECT
obj.name [name], tab.max_column_id_used [cols], ind.rows [rows], tab.create_date, tab.modify_date
FROM sysobjects as obj
INNER JOIN sysindexes as ind on obj.id = ind.id
INNER JOIN sys.tables as tab on obj.id = tab.object_id
WHERE obj.xtype = 'U' AND ind.indid < 2
ORDER BY name";
			$this->redirect('default', $dot, 1);
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
			$this->redirect('default', $dot, 1);
		}		
		
		
		
	}
	

}
