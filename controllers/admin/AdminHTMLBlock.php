<?php
/**
 * Copyright (C) 2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */


class AdminHTMLBlockController extends ModuleAdminController
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = true;
        $this->identifier = 'id_block';
        $this->table = 'tbhtmlblock';

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {

        if (empty($this->display) || $this->display =='list') {
            $this->page_header_toolbar_btn['new_block'] = [
                'href' => static::$currentIndex.'&addtbhtmlblock&token='.$this->token,
                'desc' => $this->l('Add new block', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }
        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
    	$blocks = $this->module->getAllBlocks();
    	$content = '';

    	if($blocks)
    	{
    		foreach ($blocks as $block) {

    			$fieldsList = [
		            'id_block' => [
		                'title' => 'ID',
		                'align' => 'center',
		                'class' => 'fixed-width-xs',
		            ],
		            'name'       => [
		                'title' => $this->l('Name'),
		            ],
		            'active'     => [
		                'title'  => $this->l('Status'),
		                'active' => 'status',
		                'type'   => 'bool',
		            ],
		            'position'     => [
		                'title'  => $this->l('Position'),
            			'position'   => 'position',
		            ],
		        ];

		        $helper = new HelperList();
		        $helper->shopLinkType = '';
		        $helper->simple_header = true;
		        $helper->actions = ["edit", "delete"];
		        $helper->show_toolbar = false;
		        $helper->module = $this;
		        $helper->listTotal = count($blocks);
		        $helper->identifier = 'id_block';
		        $helper->position_identifier = 'position';
		        $helper->title = $block['name'];
		        $helper->orderBy = 'position';
		        $helper->orderWay = 'ASC';
		        $helper->table = $this->table;
		        $helper->token = Tools::getAdminTokenLite('AdminHTMLBlock');
		        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		        $content .= $helper->generateList($block['blocks'], $fieldsList);
    		}

    	}
    	return $content;

    }


	public function renderForm()
	{
		$inputs[] = array(
			'type' => 'text',
			'label' => $this->l('Block Name (For your eyes only)'),
			'name' => 'name',
			);
		$inputs[] = array(
			'type' => 'textarea',
			'label' => $this->l('Content'),
			'name' => 'content',
			'lang' => true,
			'autoload_rte' => true
			);

		$inputs[] = array(
			'type' => 'select',
			'label' => $this->l('Hook'),
			'name' => 'hook_name',
			'options' => [
	                'query' => $this->module->getHooksWithNames(),
	                'id'    => 'name',
	                'name'  => 'title',
	            ],
			);
		$inputs[] = [
			'type'   => 'switch',
            'label'  => $this->l("Active"),
            'name'   => 'active',
            'values' => [
                [
                    'id'    => 'active_on',
                    'value' => 1,
                ],
                [
                    'id'    => 'active_off',
                    'value' => 0,
                ],
            ]
        ];


		if($this->display == 'edit')
		{
			$inputs[] = array(
				'type' => 'hidden',
				'name' => 'id_block',
			);
			$title = $this->l('Edit Block');
			$action = 'submitEditBlock';
			$this->fields_value = $this->module->getSingleBlockData(Tools::getValue('id_block'));
		} else {
			$title = $this->l('Add new Entry');
			$action = 'submitAddBlock';
		}


		$this->fields_form = array(
			'legend' => array(
				'title' => $title,
				'icon' => 'icon-cogs'
				),
			'input' => $inputs,
			'submit' => array(
				'title' =>$this->l('Save'),
				'class' => 'btn btn-default pull-right',
				'name' => $action
				)
			);

		return parent::renderForm();

	}

	public function renderView()
	{
		$this->tpl_view_vars['object'] = $this->loadObject();
		return parent::renderView();
	}

	public function postProcess()
	{
		if ($this->ajax) {
			$action = Tools::getValue('action');
			if (!empty($action) && method_exists($this, 'ajaxProcess'.Tools::toCamelCase($action))) {
                $return = $this->{'ajaxProcess'.Tools::toCamelCase($action)}();
            }
		}
		else {

			if (Tools::isSubmit('submitAddBlock'))
			{
				$this->processAdd();

			}
			else if(Tools::isSubmit('submitEditBlock'))
			{
				$this->processUpdate();
			}
			else if (Tools::isSubmit('status'.$this->table))
			{
				$this->toggleStatus();
			}
			else if(Tools::isSubmit('delete'.$this->table) && Tools::isSubmit('id_block'))
			{
				$this->processDelete();
			}

		}



	}
	public function toggleStatus()
	{
		$id_block = (int)Tools::getValue('id_block');
		Db::getInstance()->update($this->module->table_name, ['active' => !$this->module->getBlockStatus($id_block)], 'id_block = '.$id_block);

		if (empty($this->errors)) {
            $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
        }
	}

	public function processAdd()
	{
		$blockname = Tools::getValue('name');

		if(!$blockname || !Validate::isGenericName($blockname))
			$this->_errors[] = $this->l('Invalid name');
		else {

			if(!Db::getInstance()->insert($this->module->table_name, ['name' => $blockname, 'active' => Tools::getValue('active')]))
				$this->_errors[] = $this->l('Error while adding the new block, please retry');
			else {

				$block_id = Db::getInstance()->Insert_ID();

				$hookname = Tools::getValue('hook_name');
				$max_p = Db::getInstance()->getValue('SELECT MAX(position) FROM ' . _DB_PREFIX_ . $this->module->table_name_hook . ' WHERE hook_name = "' . pSQL($hookname).'"');

				if($max_p === false)
					$max_p = 0;
				else
					$max_p++;

				$hook_data = ['id_block' => $block_id, 'hook_name' => pSQL($hookname), 'position' => $max_p];

				if(!Db::getInstance()->insert($this->module->table_name_hook, $hook_data))
				{
					Db::getInstance()->delete($this->module->table_name, 'id_block = ' . $block_id);
					$this->_errors[] = $this->l('Error while adding the hook. ');
				}
				else {
					foreach ($this->getLanguages() as $lang) {
						$content = Tools::getValue('content_'.$lang['id_lang']);
						if(!Db::getInstance()->insert($this->module->table_name_lang, ['id_block' => $block_id, 'id_lang' => $lang['id_lang'], 'content' => pSQL($content, TRUE)]))
							$this->_errors[] = $this->l('Error while adding the block\'s content for language "'.$lang['id_lang'].'"');
					}

				}

			}
		}
		if (empty($this->errors)) {
            $this->redirect_after = static::$currentIndex.'&conf=3&token='.$this->token;
        }
	}

	public function processUpdate()
	{
		$blockname = Tools::getValue('name');
		if(!$blockname || !Validate::isGenericName($blockname))
			$this->_erros[] = $this->l('Invalid name');
		else {
			if (!Db::getInstance()->update($this->module->table_name, ['name' => $blockname, 'active' => Tools::getValue('active')], 'id_block = '. (int)Tools::getValue('id_block')))
				$this->_errors[] = $this->l('Error while updating the block ');
			else
			{
				if (!Db::getInstance()->update($this->module->table_name_hook, ['hook_name' => pSQL(Tools::getValue('hook_name'))], 'id_block = '. (int)Tools::getValue('id_block')))
					$this->_errors[] = $this->l('Error while updating the hook ');
				else {
					foreach ($this->getLanguages() as $lang)
					{
						$content = Tools::getValue('content_'.$lang['id_lang']);

						// add the language if not present
						$is_lang_added = Db::getInstance()->getValue('SELECT id_block FROM '._DB_PREFIX_.$this->module->table_name_lang.' WHERE id_block = '.(int)Tools::getValue('id_block').' AND id_lang = ' . $lang['id_lang']);
						if(!$is_lang_added)
							Db::getInstance()->insert($this->module->table_name_lang, array('id_lang' => $lang['id_lang'], 'id_block' => (int)Tools::getValue('id_block'), 'content' => ''));

						if(!Db::getInstance()->update($this->module->table_name_lang, array('content' => pSQL($content, TRUE)), 'id_block = '.(int)Tools::getValue('id_block').' AND id_lang = ' . $lang['id_lang']))
							$this->_errors[] = $this->l('Error while updating the block\'s content for language "'.$lang['id_lang'].'"');
					}
				}
			}

		}
		if (empty($this->errors)) {
            $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
        }
	}

	public function processDelete()
	{
		$id_block = Tools::getValue('id_block');
		Db::getInstance()->delete($this->module->table_name, 'id_block = ' . $id_block);
		Db::getInstance()->delete($this->module->table_name_hook, 'id_block = ' . $id_block);
		Db::getInstance()->delete($this->module->table_name_lang, 'id_block = ' . $id_block);

        $this->redirect_after = static::$currentIndex.'&conf=1&token='.$this->token;
	}

	public function ajaxProcessUpdatePositions()
	{
		$positions = Tools::getValue('block');

		foreach ($positions as $position => $value) {
            $pos = explode('_', $value);
            Db::getInstance()->update($this->module->table_name_hook, ['position' => $position], 'id_block =' . (int)$pos[2]);

        }

	}
}
