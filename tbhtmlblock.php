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

if ( ! defined('_TB_VERSION_')) {
    exit;
}

/**
 * Added back office hooks
 **/
class TbHtmlBlock extends Module
{
    // database tables
    const TABLE_NAME = 'tbhtmlblock';
    const TABLE_NAME_LANG = 'tbhtmlblock_lang';
    const TABLE_NAME_HOOK = 'tbhtmlblock_hook';

    // List of hooks
    const HOOK_LIST = [
        'displayHeader',
        'displayLeftColumn',
        'displayRightColumn',
        'displayHome',
        'displayTop',
        'displayFooter',
        'displayFooterProduct',
        'displayMyAccountBlock',
        'displayBackOfficeFooter',
        'displayBackOfficeHeader',
        'displayBackOfficeHome',
        'displayBackOfficeTop',
        'displayBackOfficeCategory',
        'displayAdminOrder',
        'displayAdminCustomers',
        'displayBeforeCarrier',
        'displayBeforePayment',
        'displayCustomerAccount',
        'displayCustomerAccountForm',
        'displayCustomerAccountFormTop',
        'displayLeftColumnProduct',
        'displayMaintenance',
        'displayRightColumnProduct',
        'displayProductTab',
        'displayProductTabContent',
        'displayPaymentReturn',
        'displayPaymentTop',
        'displayProductButtons',
        'displayProductComparison',
        'displayShoppingCart',
        'displayShoppingCartFooter',
        'displayTopColumn',
        'displayProductListFunctionalButtons',
        'displayPDFInvoice',
        'displayInvoice',
        'displayNav',
        'displayMyAccountBlockFooter',
        'displayHomeTab',
        'displayHomeTabContent',
    ];

    /**
     * @var array
     */
    protected static $cachedHooksList = null;

    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'tbhtmlblock';
        $this->tab = 'front_office_features';
        $this->version = '1.2.0';
        $this->author = 'thirty bees';
        $this->tb_min_version = '1.0.0';
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('HTML Block');
        $this->description = $this->l('Add custom html or code anywhere in your theme');
    }

    /**
     * @return bool
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install($installFixtures = true)
    {
        if ( ! parent::install()
            || ! $this->createTab()
            || ! $this->installTable()
        ) {
            return false;
        }

        foreach (static::HOOK_LIST as $hook) {
            if ( ! $this->registerHook($hook)) {
                return false;
            }
        }

        if ($installFixtures) {
            $this->installFixtures();
        }

        return true;
    }

    /**
     * @param boolean $full
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall($full = true)
    {
        if ( ! parent::uninstall()
            || ! $this->eraseTable($full)
            || ! $this->eraseTabs()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function reset()
    {
        return (
            $this->uninstall(false) &&
            $this->install(false)
        );
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    private function installTable(){
        $sql = 'CREATE TABLE  IF NOT EXISTS `'._DB_PREFIX_ . static::TABLE_NAME . '` (
                `id_block` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(64) NOT NULL,
                `active` TINYINT(1) NOT NULL,
                PRIMARY KEY (`id_block`)
                ) ENGINE =' ._MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $sql2 = 'CREATE TABLE  IF NOT EXISTS `'._DB_PREFIX_.static::TABLE_NAME_LANG.'` (
                `id_block` INT(11) UNSIGNED NOT NULL,
                `id_lang` INT(11) UNSIGNED NOT NULL,
                `content` TEXT NOT NULL,
                PRIMARY KEY (`id_block`, `id_lang`)
                ) ENGINE =' ._MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $sql3 = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.static::TABLE_NAME_HOOK.'` (
                `id_block` INT(11) UNSIGNED NOT NULL,
                `hook_name` VARCHAR(64) NOT NULL,
                `position` INT(11) UNSIGNED NOT NULL,
                PRIMARY KEY (`id_block`,  `hook_name`)
                ) ENGINE =' ._MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        if ( ! Db::getInstance()->Execute($sql)
            || ! Db::getInstance()->Execute($sql2)
            || ! Db::getInstance()->Execute($sql3)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    private function eraseTable($deleteTables){
        if ($deleteTables) {
            $conn = Db::getInstance();
            return (
                $conn->execute('DROP TABLE `' . _DB_PREFIX_ . static::TABLE_NAME . '`') &&
                $conn->execute('DROP TABLE `' . _DB_PREFIX_ . static::TABLE_NAME_LANG . '`') &&
                $conn->execute('DROP TABLE `' . _DB_PREFIX_ . static::TABLE_NAME_HOOK . '`')
            );
        }
        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    private function createTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminHTMLBlock';
        $tab->module = $this->name;
        $tab->id_parent = 0;
        foreach (Language::getIDs(false) as $langId) {
            $tab->name[$langId] = $this->displayName;
        }

        if ($tab->add()) {
            return true;
        }

        return false;
    }

    /**
     * Get rid of all installed back office tabs
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function eraseTabs()
    {
        foreach (Tab::getCollectionFromModule($this->name) as $tab) {
            $tab->delete();
        }
        return true;
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAllBlocks()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT b.*, bh.*, h.title as hook_title
            FROM '._DB_PREFIX_.static::TABLE_NAME.' b
            LEFT JOIN '._DB_PREFIX_.static::TABLE_NAME_HOOK.' bh ON (bh.id_block = b.id_block)
            LEFT JOIN '._DB_PREFIX_.'hook h ON (h.name = bh.hook_name)
            GROUP BY b.id_block
            ORDER BY bh.hook_name, bh.position
        ');

        if ( ! $result) {
            return [];
        }

        $finalBlocks = [];
        foreach ($result as $block) {
            $finalBlocks[$block['hook_name']]['name'] = $block['hook_title'];
            $finalBlocks[$block['hook_name']]['blocks'][] = $block;
        }

        return $finalBlocks;
    }

    /**
     * Returns custom blocks contents, indexed by hook name
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getFrontBlocks()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT b.content, bh.hook_name
            FROM '._DB_PREFIX_.static::TABLE_NAME_LANG.' b
            LEFT JOIN '._DB_PREFIX_.static::TABLE_NAME_HOOK.' bh ON (bh.id_block = b.id_block)
            LEFT JOIN '._DB_PREFIX_.static::TABLE_NAME.' o ON (o.id_block = b.id_block)
            WHERE id_lang = '.(int)$this->context->language->id.'
            AND o.active = 1
            GROUP BY b.id_block
            ORDER BY bh.hook_name, bh.position
        ');

        if ( ! $result) {
            return [];
        }

        $finalBlocks = [];
        foreach ($result as $block) {
            $finalBlocks[$block['hook_name']][] = $block['content'];
        }

        return $finalBlocks;
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getHooksWithNames()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT *
            FROM '._DB_PREFIX_.'hook
            WHERE name IN ("'.implode('","', static::HOOK_LIST).'")
            ORDER BY title
        ');

        return is_array($result) ? $result : [];
    }

    /**
     * @param int $blockId
     * @return false|array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getSingleBlockData($blockId)
    {
        $blockId = (int)$blockId;

        $sql = ('
            SELECT *
            FROM '._DB_PREFIX_.static::TABLE_NAME .' t
            LEFT JOIN '._DB_PREFIX_.static::TABLE_NAME_LANG.' tl ON (t.id_block = tl.id_block)
            LEFT JOIN '._DB_PREFIX_.static::TABLE_NAME_HOOK.' th ON (t.id_block = th.id_block)
            WHERE t.id_block ='.$blockId.'
        ');
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($sql);

        if (! $result) {
            return false;
        }

        $newBlock = $result[0];

        $newBlock['content'] = [];
        foreach ($result as $block) {
            $newBlock['content'][$block['id_lang']] = $block['content'];
        }

        foreach (Language::getIDs(false) as $langId) {
            if (! array_key_exists($langId, $newBlock['content'])) {
                $newBlock['content'][$langId] = '';
            }
        }

        return $newBlock;
    }

    /**
     * @param int $id_block
     * @return bool
     * @throws PrestaShopException
     */
    public function getBlockStatus($id_block)
    {
        return (bool)Db::getInstance()->getValue('SELECT active FROM '._DB_PREFIX_.static::TABLE_NAME.' WHERE id_block = '.(int)$id_block);
    }

    /**
     * Common hook handler - output custom content
     *
     * @param string $hookName
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookCommon($hookName)
    {
        if (is_null(static::$cachedHooksList)) {
            static::$cachedHooksList = $this->getFrontBlocks();
        }

        if ( ! isset(static::$cachedHooksList[$hookName])) {
            return '';
        }
        $this->smarty->assign('tbhtmlblock_blocks', static::$cachedHooksList[$hookName]);
        return $this->display(__FILE__, 'tbhtmlblock.tpl');
    }

    /**
     * Hook handler 
     * 
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHeader()
    {
        return $this->hookCommon('displayHeader');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayLeftColumn()
    {
        return $this->hookCommon('displayLeftColumn');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayRightColumn()
    {
        return $this->hookCommon('displayRightColumn');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHome()
    {
        return $this->hookCommon('displayHome');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayTop()
    {
        return $this->hookCommon('displayTop');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayFooter()
    {
        return $this->hookCommon('displayFooter');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayFooterProduct()
    {
        return $this->hookCommon('displayFooterProduct');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayMyAccountBlock()
    {
        return $this->hookCommon('displayMyAccountBlock');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBackOfficeFooter()
    {
        return $this->hookCommon('displayBackOfficeFooter');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBackOfficeHeader()
    {
        return $this->hookCommon('displayBackOfficeHeader');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBackOfficeHome()
    {
        return $this->hookCommon('displayBackOfficeHome');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBackOfficeTop()
    {
        return $this->hookCommon('displayBackOfficeTop');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBackOfficeCategory()
    {
        return $this->hookCommon('displayBackOfficeCategory');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayAdminOrder()
    {
        return $this->hookCommon('displayAdminOrder');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayAdminCustomers()
    {
        return $this->hookCommon('displayAdminCustomers');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBeforeCarrier()
    {
        return $this->hookCommon('displayBeforeCarrier');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBeforePayment()
    {
        return $this->hookCommon('displayBeforePayment');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayCustomerAccount()
    {
        return $this->hookCommon('displayCustomerAccount');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayCustomerAccountForm()
    {
        return $this->hookCommon('displayCustomerAccountForm');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayCustomerAccountFormTop()
    {
        return $this->hookCommon('displayCustomerAccountFormTop');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayLeftColumnProduct()
    {
        return $this->hookCommon('displayLeftColumnProduct');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayMaintenance()
    {
        return $this->hookCommon('displayMaintenance');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayRightColumnProduct()
    {
        return $this->hookCommon('displayRightColumnProduct');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayProductTab()
    {
        return $this->hookCommon('displayProductTab');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayProductTabContent()
    {
        return $this->hookCommon('displayProductTabContent');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayPayment()
    {
        return $this->hookCommon('displayPayment');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayPaymentReturn()
    {
        return $this->hookCommon('displayPaymentReturn');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayPaymentTop()
    {
        return $this->hookCommon('displayPaymentTop');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayProductButtons()
    {
        return $this->hookCommon('displayProductButtons');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayProductComparison()
    {
        return $this->hookCommon('displayProductComparison');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayShoppingCart()
    {
        return $this->hookCommon('displayShoppingCart');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayShoppingCartFooter()
    {
        return $this->hookCommon('displayShoppingCartFooter');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayTopColumn()
    {
        return $this->hookCommon('displayTopColumn');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayProductListFunctionalButtons()
    {
        return $this->hookCommon('displayProductListFunctionalButtons');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayPDFInvoice()
    {
        return $this->hookCommon('displayPDFInvoice');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayInvoice()
    {
        return $this->hookCommon('displayInvoice');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayNav()
    {
        return $this->hookCommon('displayNav');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayMyAccountBlockFooter()
    {
        return $this->hookCommon('displayMyAccountBlockFooter');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHomeTab()
    {
        return $this->hookCommon('displayHomeTab');
    }

    /**
     * Hook handler
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHomeTabContent()
    {
        return $this->hookCommon('displayHomeTabContent');
    }

    /**
     * Get module configuration page.
     *
     * Redirects to AdminHTMLBlock controller
     *
     * @throws PrestaShopException
     */
    public function getContent()
    {
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminHTMLBlock'));
    }

    /**
     * Creates simple data fixtures
     * @throws PrestaShopException
     */
    private function installFixtures()
    {
        $conn = Db::getInstance();
        $conn->insert(static::TABLE_NAME, ['name' => 'Store Information', 'active' => 1]);
        $blockId = (int)$conn->Insert_ID();
        $conn->insert(static::TABLE_NAME_HOOK, ['id_block' => $blockId, 'hook_name' => 'displayFooter', 'position' => 0]);
        $content = (
            '<section id="blockcontactinfos" class="col-xs-12 col-sm-3">'.
            '  <h2 class="footer-title section-title-footer">Store Information</h2>'.
            '  <address>'.
            '    <ul class="list-unstyled">'.
            '      <li><b>Your Company</b></li>'.
            '      <li>42 Bee Lane<br /> 12345 The Hive<br /> the Netherlands</li>'.
            '      <li><i class="icon icon-phone"></i> <a href="tel:0123-456-789">0123-456-789</a></li>'.
            '      <li><i class="icon icon-envelope-alt"></i> <a href="mailto:%73%61%6c%65%73@%79%6f%75%72%63%6f%6d%70%61%6e%79.%63%6f%6d">sales@yourcompany.com</a></li>'.
            '    </ul>'.
            '  </address>'.
            '</section>'
        );
        foreach (Language::getIDs(false) as $langId) {
            $conn->insert(static::TABLE_NAME_LANG, [
                'id_block' => $blockId,
                'id_lang' => (int)$langId,
                'content' => $content
            ]);
        }
    }
}
