<?php

/**
 * Navigation node
 *
 * @author Jan Marek
 * @license MIT
 */

namespace Navigation;

use Nette\ComponentModel\Container;


class NavigationNode extends Container
{

	/** @var string */
	public $label;

	/** @var string */
	public $url;

	/** @var string */
	public $tag;

	/** @var bool */
	public $isMain = true;

	/** @var bool */
	public $isHidden = false;

	/** @var bool */
	public $isCurrent = false;



	/**
	 * Add navigation node as a child
	 * @staticvar int $counter
	 * @param string $label
	 * @param string $url
	 * @return NavigationNode
	 */
	public function add($label, $url, $tag='', $isMain=true, $isHidden=false) {
		$navigationNode = new self;
		$navigationNode->label = $label;
		$navigationNode->url = $url;
		$navigationNode->tag = $tag;
		$navigationNode->isMain = $isMain;
		$navigationNode->isHidden = $isHidden;

		static $counter;
		$this->addComponent($navigationNode, ++$counter);

		return $navigationNode;
	}



	/**
	 * Set node as current
	 * @param NavigationNode $node
	 */
	public function setCurrent(NavigationNode $node)
	{
		return $this->parent->setCurrent($node);
	}

	/**
	 * Set node as main
	 * @param NavigationNode $node
	 */
	public function setMain(NavigationNode $node)
	{
		return $this->parent->setMain($node);
	}

}
