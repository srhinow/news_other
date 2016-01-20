<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Front end module "news list".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleNewsOtherList extends \ModuleNews
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_newslist';

	protected $news_archives = array();

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var \BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['newsOtherlist'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['items']) && \Config::get('useAutoItem') && isset($_GET['auto_item']))
		{
			\Input::setGet('items', \Input::get('auto_item'));
		}

		// Do not index or cache the page if no news item has been specified
		if (!\Input::get('items'))
		{
			/** @var \PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$limit = null;
		$offset = intval($this->skipFirst);


		// Maximum number of items
		if ($this->numberOfItems > 0)
		{
			$limit = $this->numberOfItems;
		}

		// Handle featured news
		if ($this->news_featured == 'featured')
		{
			$blnFeatured = true;
		}
		elseif ($this->news_featured == 'unfeatured')
		{
			$blnFeatured = false;
		}
		else
		{
			$blnFeatured = null;
		}

		$this->Template->articles = array();
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

		$objCurrArticle = \NewsModel::findByIdOrAlias(\Input::get('items'));
		if(!is_object($objCurrArticle))  return;

		//nur das Archiv von der aktuell angezeigten News ist relevant
		$this->news_archives = array($objCurrArticle->pid);

		// Get the total number of items
		$intTotal = $this->countItems($this->news_archives, $blnFeatured) -1;

		if ($intTotal < 1)
		{
			return;
		}

		$total = $intTotal - $offset;

		// Split the results
		if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage))
		{
			// Adjust the overall limit
			if (isset($limit))
			{
				$total = min($limit, $total);
			}

			// Get the current page
			$id = 'page_n' . $this->id;
			$page = (\Input::get($id) !== null) ? \Input::get($id) : 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				/** @var \PageModel $objPage */
				global $objPage;

				/** @var \PageError404 $objHandler */
				$objHandler = new $GLOBALS['TL_PTY']['error_404']();
				$objHandler->generate($objPage->id);
			}

			// Set limit and offset
			$limit = $this->perPage;
			$offset += (max($page, 1) - 1) * $this->perPage;
			$skip = intval($this->skipFirst);

			// Overall limit
			if ($offset + $limit > $total + $skip)
			{
				$limit = $total + $skip - $offset;
			}

			// Add the pagination menu
			$objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$objArticles = $this->fetchOtherItems($this->news_archives, $objCurrArticle->id, $blnFeatured, ($limit ?: 0), $offset);

		// Add the articles
		if ($objArticles !== null)
		{
			$this->Template->articles = $this->parseArticles($objArticles);
		}

		$this->Template->archives = $this->news_archives;
	}


	/**
	 * Count the total matching items
	 *
	 * @param array $newsArchives
	 * @param boolean $blnFeatured
	 *
	 * @return integer
	 */
	protected function countItems($newsArchives, $blnFeatured)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['newsListCountItems']) && is_array($GLOBALS['TL_HOOKS']['newsListCountItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['newsListCountItems'] as $callback)
			{
				if (($intResult = \System::importStatic($callback[0])->{$callback[1]}($newsArchives, $blnFeatured, $this)) === false)
				{
					continue;
				}

				if (is_int($intResult))
				{
					return $intResult;
				}
			}
		}

		return \NewsModel::countPublishedByPids($newsArchives, $blnFeatured);
	}


	/**
	 * Fetch the matching items
	 *
	 * @param  array   $newsArchives
	 * @param  integer $currId
	 * @param  boolean $blnFeatured
	 * @param  integer $limit
	 * @param  integer $offset
	 *
	 * @return \Model\Collection|\NewsModel|null
	 */
	protected function fetchOtherItems($newsArchives, $currId, $blnFeatured, $limit, $offset)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['newsListFetchItems']) && is_array($GLOBALS['TL_HOOKS']['newsListFetchItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['newsListFetchItems'] as $callback)
			{
				if (($objCollection = \System::importStatic($callback[0])->{$callback[1]}($newsArchives, $blnFeatured, $limit, $offset, $this)) === false)
				{
					continue;
				}

				if ($objCollection === null || $objCollection instanceof \Model\Collection)
				{
					return $objCollection;
				}
			}
		}

		return \NewsOtherModel::findPublishedByPidsWithoutCurrent($newsArchives, $currId, $blnFeatured, $limit, $offset);
	}
}
