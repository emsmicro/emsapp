<?php

/*
 * Sprava presenter
 */

class SpravaPresenter extends SecuredPresenter
{
	/**
	 * @var array
	 * @navigace
	 */
	private $navigace;
	/** Title constants */
    const TITUL_DEFAULT = 'Stránka správy';
    const TITUL_SUBTITL = 'Přehled funkcí';
	private $todos	= array(
							'Uživatelé'	=> array(
											'Přehled uživatelů systému.'=> 'Uzivatel:default',
											'Přidání nového uživatele.'=> 'Uzivatel:add',
											'Přidělení rolí uživatelům.'=> ''
												),
							'Místa'		=> array(
											'Přehled číselníků míst.'=>'Misto:default',
											'Přidání nového státu.'=>'Misto:adds',
											'Přidání nového kraje.'=>'Misto:addk',
											'Přidání nové obce.'=>'Misto:addo',
											),
							'Číselníky'	=> array(
											'Přehled číselníků systému.'=>'Ciselnik:default',
											),
							);


	public function startup()
	{
		parent::startup();
		$section = $this->context->session->getSection('mySetting');
		$this->template->afirma = $section->firma;
	    $menu = array(
	            'Správa'	=> 'Sprava',
	            'Uživatelé'	=> 'Uzivatel',
				'Práva'		=> 'Prava',
				'Agendy'	=> 'Agenda',
				'Tarify'	=> 'SetTarifu',
	            'Místa'		=> 'Misto',
				'Číselníky'	=> 'Ciselnik',
				'Query'		=> 'Query',
	            'Obchod'	=> 'Obchod',  //další moduly zařadit až na konec menu
	            'TPV'		=> 'Tpv',
	            'Nákup'		=> 'Nakup'
		        );
        $this->navigace = $menu;
	}
	
	/**
	 * @return void
	 */
	public function renderDefault()
	{
        $this->template->titul = self::TITUL_DEFAULT;
        $this->template->subtitle = self::TITUL_SUBTITL;
		$this->template->is_todo = $this->is_todo;
		if($this->is_todo){$this->template->todos = $this->todos;}
	}

	public function renderPhpinfo()
	{
		
		ob_start();
		phpinfo();
		$info_arr = array();
		$info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
		$cat = "General";
		foreach($info_lines as $line)
		{
			// new cat?
			preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
			if(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
			{
				$info_arr[$cat][$val[1]] = $val[2];
			}
			elseif(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
			{
				$info_arr[$cat][$val[1]] = array("local" => $val[2], "master" => $val[3]);
			}
		}
						
		$this->template->phpinfo = $info_arr;
		ob_end_clean();
		//dd($info_arr);
	}
	
	
	/**
	 * Vytváří vrchní navigační panel
	 * @param array
	*/
	protected function createComponentNavigation($name) {
		$this->MakeMenu($name, $this->navigace);
	}

}
