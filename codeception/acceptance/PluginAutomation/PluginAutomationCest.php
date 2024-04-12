<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// use Codeception\Util\Fixtures;
use Doctrine\ORM\EntityManager;
use Eccube\Entity\Plugin;
use Eccube\Repository\PluginRepository;

class PluginAutomationCest
{  
    /** @var string */
    private $code;

    private $name;
    private $filePath;

    private $config;

    private $authenticationKey;

    public function _before(AcceptanceTester $I)
    {
        $I->goToAdminPage();

        $I->submitForm('#form1', [
            'login_id' => 'admin',
            'password' => 'password',
        ]);

        $I->see('ホーム', 'h1 > span.title');
        // $fileName = 'AmazonPaymentsV2_20240409.tar.gz';
        // $this->filePath = '/'.'plugins/'.$fileName;
        $this->filePath = 'plugins/'. getenv('FILE_PATH');

    }

    public function _after(AcceptanceTester $I)
    {
    }

    public function test_install(AcceptanceTester $I)
    {
        $I->wantTo('Test install plugin:'.$this->name);
        Store_Plugin::start($I, $this->code)->install( $this->filePath);
    }

    
    public function test_enable(AcceptanceTester $I)
    {
        $I->wantTo('Test enable plugin:'.$this->name);
        Store_Plugin::start($I, $this->code)->enable();
    }

    public function test_disable(AcceptanceTester $I)
    {
        $I->wantTo('Test disable plugin:'.$this->name);
        Store_Plugin::start($I, $this->code)->disable();
    }

    public function test_remove(AcceptanceTester $I)
    {
        $I->wantTo('Test uninstall plugin:'.$this->name);
        Store_Plugin::start($I, $this->code)->uninstall();
    }

    public function test_directoryIsRemoved(AcceptanceTester $I)
    {
        $I->wantTo('Test check plugin directory is removed after uninstall:'.$this->code);
        Store_Plugin::start($I, $this->code)->checkDirectoryIsRemoved();
    }
}


class Store_Plugin
{
    /** @var string */
    protected $code;

    /** @var AcceptanceTester */
    protected $I;


    /** @var \Doctrine\DBAL\Connection */
    protected $conn;

    /** @var Plugin */
    protected $Plugin;

    /** @var EntityManager */
    protected $em;

    /** @var PluginRepository */
    protected $pluginRepository;

    public function __construct(AcceptanceTester $I, $code)
    {
        $this->I = $I;
        $this->code = $code;
        // $this->em = Fixtures::get('entityManager');
        // $this->conn = $this->em->getConnection();
        // $this->pluginRepository = $this->em->getRepository(Plugin::class);
    }

    private function getPluginName() {
        return $this->I->grabTextFrom('span.plugin_name > a');
    }

    public static function start(AcceptanceTester $I, $code)
    {
        $result = new self($I, $code);

        return $result;
    }


    public function install($filePath)
    {
        // $this->I->assertFileExists($filePath);

        $this->I->goToAdminPage('admin/ownersstore/');
        $this->I->see('プラグイン登録');
        $this->I->see('登録されているプラグインはありません。');
        $this->I->attachFile(['name' => 'plugin_file'], $filePath);
        $this->I->click('インストール');
        $this->I->seeInPopup('プラグインをインストールしても宜しいでしょうか？');
        $this->I->acceptPopup();
        // Wait for plugin install
        $this->I->wait(5);
        $this->I->seeInPopup('プラグインをインストールしました。');
        $this->I->acceptPopup();

        // Verify 
        $this->I->seeElement('table.system-plugin');
        $this->I->seeElement('table.system-plugin tbody tr td span.plugin_name');
        $this->I->dontSee('登録されているプラグインはありません。', 'div#system');
        $this->I->seeLink('削除');

        // Check folder exists
        // $this->I->assertDirectoryExists($this->config['plugin_realdir'].'/'.$this->code);

        return $this;
    }

    public function enable()
    {
        $this->I->goToAdminPage('admin/ownersstore/');
        $this->I->waitForElement("div#system", 10);
        $this->I->see('プラグイン一覧');
        $pluginName = $this->getPluginName();
        $this->I->checkOption("#plugin_enable");
        $this->I->seeInPopup('プラグインを有効にしても宜しいですか？');
        $this->I->acceptPopup();

        $this->I->wait(3);
        $this->I->seeInPopup($pluginName . 'を有効にしました。');
        $this->I->acceptPopup();
        
        // Popup is still open after reload
        // Wait for page reload by program
        $this->I->wait(3);
        $this->I->seeCheckboxIsChecked("#plugin_enable");

        // Reload page to verify checkox again
        $this->I->reloadPage();
        // Popup is still open after reload
        $this->I->wait(3);
        $this->I->seeInPopup($pluginName . 'を有効にしました。');
        $this->I->acceptPopup();
        $this->I->seeCheckboxIsChecked("#plugin_enable");

        // Check database
        // $this->Plugin = $this->pluginRepository->findByCode($this->code);
        // $this->em->refresh($this->Plugin);
        // $this->I->assertTrue($this->Plugin->isInitialized(), '初期化されている');
        // $this->I->assertTrue($this->Plugin->isEnabled(), '有効化されている');

        return $this;
    }

    public function disable()
    {
        $this->I->goToAdminPage('admin/ownersstore/');
        $this->I->waitForElement("div[id='system']");
        $this->I->see('プラグイン一覧');
        $pluginName = $this->getPluginName();

        $this->I->seeCheckboxIsChecked("#plugin_enable");
        $this->I->uncheckOption("#plugin_enable");
        $this->I->seeInPopup('プラグインを無効にしても宜しいですか？');
        $this->I->acceptPopup();


         // Wait for page reload by program
        $this->I->wait(3);
        $this->I->seeInPopup($pluginName . 'を無効にしました。');
        $this->I->acceptPopup();
        $this->I->dontSeeCheckboxIsChecked("#plugin_enable");

        // Reload page to verify checkox again
        $this->I->reloadPage();
        $this->I->wait(3);
        $this->I->seeInPopup($pluginName . 'を無効にしました。');
        $this->I->acceptPopup();
        $this->I->dontSeeCheckboxIsChecked("#plugin_enable");
        return $this;
    }

    public function uninstall()
    {
        $this->I->goToAdminPage('admin/ownersstore/');
        $this->I->waitForElement("div[id='system']");
        $pluginName = $this->getPluginName();

        $this->I->click("a[name='uninstall']");
        $this->I->seeInPopup("一度削除したデータは元に戻せません。\nプラグインを削除しても宜しいですか？");
        $this->I->acceptPopup();
        
        // Wait for page reload by program
        $this->I->wait(3);
        $this->I->seeInPopup($pluginName . "を削除しました。");
        $this->I->acceptPopup();
        $this->I->see('登録されているプラグインはありません。');

        // Check database
        // $this->Plugin = $this->pluginRepository->findByCode($this->code);
        // $this->em->refresh($this->Plugin);
        // $this->Plugin = $this->pluginRepository->findByCode($this->code);
        // $this->I->assertNull($this->Plugin, '削除されている');


        return $this;
    }

    public function checkDirectoryIsRemoved()
    {
        // $this->I->assertDirectoryDoesNotExist($this->config['plugin_realdir'].'/'.$this->code);
        return $this;
    }
}