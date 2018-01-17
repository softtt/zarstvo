<?php
require_once _PS_MODULE_DIR_ . 'ph_relatedposts/ph_relatedposts.php';
class AdminSimpleBlogRelatedPostsController extends ModuleAdminController
{
	public function __construct()
	{
		$this->table = 'simpleblog_related_post';
		$this->className = 'SimpleBlogRelatedPost';

		$this->fields_list = array(
			'id_simpleblog_related_post' => array(
				'title' => $this->l('ID'),
				'align' => 'center',
				'width' => 25,
			),
			'id_simpleblog_post' => array(
				'title' => $this->l('Post ID'),
				'width' => 25,
				'align' => 'center',
			),
			'title' => array(
				'title' => $this->l('Post title'),
				'width' => '50%',
				'filter_key' => 'sbpl!meta_title'
			),
			'id_product' => array(
				'title' => $this->l('Product ID'),
				'width' => 25,
				'align' => 'center',
			),
			'product_title' => array(
				'title' => $this->l('Product'),
				'width' => '50%',
				'filter_key' => 'pl!name'
			),
		);

	 	$this->bulk_actions = array('delete' => array('text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')));

		parent::__construct();
	}

	public function renderList()
	{
	 	$this->addRowAction('delete');
//LEFT JOIN `'._DB_PREFIX_.'simpleblog_related_post` sbrp
				//ON (a.`id_simpleblog_tag` = pt.`id_simpleblog_tag`)
		$this->_select = 'sbpl.meta_title as title, pl.name as product_title';
		$this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'simpleblog_post_lang` sbpl
				ON (sbpl.`id_lang` = '.$this->context->language->id.' AND sbpl.`id_simpleblog_post` = a.`id_simpleblog_post`)
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
				ON (pl.`id_lang` = '.$this->context->language->id.' AND pl.`id_product` = a.`id_product`)';
		$this->_group = 'GROUP BY a.`id_simpleblog_related_post`';

		return parent::renderList();
	}
}


