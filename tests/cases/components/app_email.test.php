<?php

App::import('Component', 'AppEmail.AppEmail');

/**
 * EmailTestComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AppEmailTestComponent extends AppEmailComponent {

    /**
     *
     * @param string $message
     * @return array
     */
    function wrap($message) {
        return $this->_wrap($message);
    }

    /**
     * smtpSend method override for testing
     *
     * @access public
     * @return mixed
     */
    function smtpSend($data, $code = '250') {
        return parent::_smtpSend($data, $code);
    }

    /**
     * Convenience setter method for testing.
     *
     * @access public
     * @return void
     */
    function setConnectionSocket(&$socket) {
        $this->__smtpConnection = $socket;
    }

    /**
     * Convenience getter method for testing.
     *
     * @access public
     * @return mixed
     */
    function getConnectionSocket() {
        return $this->__smtpConnection;
    }

    /**
     * Convenience setter for testing.
     *
     * @access public
     * @return void
     */
    function setHeaders($headers) {
        $this->__header += $headers;
    }

    /**
     * Convenience getter for testing.
     *
     * @access public
     * @return array
     */
    function getHeaders() {
        return $this->__header;
    }

    /**
     * Convenience setter for testing.
     *
     * @access public
     * @return void
     */
    function setBoundary() {
        $this->__createBoundary();
    }

    /**
     * Convenience getter for testing.
     *
     * @access public
     * @return string
     */
    function getBoundary() {
        return $this->__boundary;
    }

    /**
     * Convenience getter for testing.
     *
     * @access public
     * @return string
     */
    function getMessage() {
        return $this->__message;
    }

    /**
     * Convenience getter for testing.
     *
     * @access protected
     * @return string
     */
    function _getMessage() {
        return $this->__message;
    }

    /**
     * Convenience method for testing.
     *
     * @access public
     * @return string
     */
    function strip($content, $message = false) {
        return parent::_strip($content, $message);
    }

    /**
     * Wrapper for testing.
     *
     * @return void
     */
    function formatAddress($string, $smtp = false) {
        return parent::_formatAddress($string, $smtp);
    }

}

/**
 * EmailTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AppEmailTestController extends Controller {

    /**
     * name property
     *
     * @var string 'AppEmailTest'
     * @access public
     */
    var $name = 'AppEmailTest';

    /**
     * uses property
     *
     * @var mixed null
     * @access public
     */
    var $uses = null;

    /**
     * components property
     *
     * @var array
     * @access public
     */
    var $components = array('Session', 'AppEmailTest');

    /**
     * pageTitle property
     *
     * @var string
     * @access public
     */
    var $pageTitle = 'AppEmailTest';

    /**
     *
     * @var AppEmailTest
     */
    var $AppEmailTest;

}

/**
 * EmailTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AppEmailComponentTestCase extends CakeTestCase {

    /**
     * Controller property
     *
     * @var AppEmailTestController
     * @access public
     */
    var $Controller;

    /**
     * name property
     *
     * @var string 'Email'
     * @access public
     */
    var $name = 'Email';

    /**
     *
     * @var AppEmailComponent
     */
    var $AppEmail;

    function skip() {
        $skip = !function_exists('mb_internal_encoding');
        if ($this->skipIf($skip, 'Missing mb_* functions, cannot run test.')) {
            return;
        }
    }

    /**
     * setUp method
     *
     * @access public
     * @return void
     */
    function setUp() {
        $this->_appEncoding = Configure::read('App.encoding');
        Configure::write('App.encoding', 'UTF-8');

        $this->Controller = & new AppEmailTestController();

        restore_error_handler();
        @$this->Controller->Component->init($this->Controller);
        set_error_handler('simpleTestErrorHandler');

        $this->Controller->AppEmailTest->initialize($this->Controller, array());
        ClassRegistry::addObject('view', new View($this->Controller));

        App::build(array(
            'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS)
        ));

        Configure::write('Email', array(
            'default' => array(
                'delivery' => 'mail',
                'sendAs' => 'text',
                'from' => 'from@example.com',
                'to' => 'to@example.com',
                'subject' => 'default subject',
                'lineLength' => '',
                'xMailer' => 'AppEmail'
            ),
            'AppEmailTest' => array(
                'subject' => 'AppEmailTest subject',
            ),
            'SomeConfig' => array(
                'subject' => 'SomeConfig subject',
            ),
        ));

        // startup
        $this->AppEmail = $this->Controller->AppEmailTest;
        $this->AppEmail->startup($this->Controller);
    }

    /**
     * tearDown method
     *
     * @access public
     * @return void
     */
    function tearDown() {
        Configure::write('App.encoding', $this->_appEncoding);
        App::build();
        $this->Controller->Session->delete('Message');
        restore_error_handler();
        ClassRegistry::flush();
    }

    /**
     * osFix method
     *
     * @param string $string
     * @access private
     * @return string
     */
    function __osFix($string) {
        return str_replace(array("\r\n", "\r"), "\n", $string);
    }

    function testStartup() {
        $AppEmailTest = new AppEmailTestComponent();
        $this->assertNull($AppEmailTest->startup($this->Controller));
        $this->assertEqual($AppEmailTest->params, Configure::read('Email'), 'Emailパラメータが読み込まれている: %s');
        $this->assertEqual($AppEmailTest->from, Configure::read('Email.default.from'), 'デフォルトのパラメータが読み込まれている: %s');
        $this->assertEqual($AppEmailTest->subject, Configure::read('Email.AppEmailTest.subject'), 'コントローラ名のパラメータが読み込まれている: %s');
    }

    function testLoad() {
        $this->assertNull($this->AppEmail->load('SomeConfig'));
        $this->assertEqual($this->AppEmail->subject, Configure::read('Email.SomeConfig.subject'), '指定した設定のパラメータが読み込まれている: %s');
    }

    function testReset() {
        $this->AppEmail->subject = 'modified subject';
        $this->assertNull($this->AppEmail->reset());
        $this->assertEqual($this->AppEmail->subject, Configure::read('Email.AppEmailTest.subject'), 'コントローラ名のパラメータが読み込まれている: %s');
        $this->AppEmail->subject = 'modified subject';
        $this->assertNull($this->AppEmail->reset('SomeConfig'));
        $this->assertEqual($this->AppEmail->subject, Configure::read('Email.SomeConfig.subject'), '指定した設定のパラメータが読み込まれている: %s');
    }

    function testWrap() {
        $AppEmailTest = new AppEmailTestComponent();
        $AppEmailTest->lineLength = '10';
        $AppEmailTest->charset = 'utf-8';

        $formatted = $AppEmailTest->wrap('1234567890abcdef');
        $this->assertEqual($formatted, array('1234567890', 'abcdef', ''), '英字の折り返し処理がされている: %s');

        $formatted = $AppEmailTest->wrap('あいうえおかきくけこ');
        $this->assertEqual($formatted, array('あいうえお', 'かきくけこ', ''), '日本語の折り返し処理がされている: %s');

        $formatted = $AppEmailTest->wrap('あいうえおかきくけ1234567890abcdef');
        $this->assertEqual($formatted, array('あいうえお', 'かきくけ', '1234567890', 'abcdef', ''), '日英字の折り返し処理がされている: %s');

        $formatted = $AppEmailTest->wrap('http://www.example.com/shomepath');
        $this->assertEqual($formatted, array('http://www.example.com/shomepath', ''), 'URLは折り返さない: %s');

        $formatted = $AppEmailTest->wrap('ftp://user@www.example.com/shomepath');
        $this->assertEqual($formatted, array('ftp://user@www.example.com/shomepath', ''), 'FTP URIは折り返さない: %s');

        $formatted = $AppEmailTest->wrap('あいうえお。かきくけこ');
        $this->assertEqual($formatted, array('あいうえお。', 'かきくけこ', ''), '行頭禁則文字処理がされている: %s');

        $formatted = $AppEmailTest->wrap('あいうえお「かきくけこ');
        $this->assertEqual($formatted, array('あいうえお', '「かきくけ', 'こ', ''), '行末禁則文字処理がされている: %s');
    }

    function testWrap_long() {
        $AppEmailTest = new AppEmailTestComponent();
        $AppEmailTest->lineLength = '72';
        $AppEmailTest->charset = 'utf-8';

        $text = 'CakePHPはPHP用の高速開発フレームワークです。アプリケーションの開発、メンテナンス、インストールのための拡張性の高い仕組みを提供します。 MVC や ORM といった、よく知られているデザインパターンを、「設定より規約優先」の考え方で利用して、CakePHPは開発コストや開発者が書く必要のあるコードを減らします。

CakePHPコミュニティでは、膨大な情報がやりとりされています。Google グループ（英語） には、質問やコメントを書き込める大きなフォーラムです。#cakephp on irc.freenode.netのIRCでも多くの情報が流れており、開発者や長年の利用者がそこにいます。 日本語情報であれば、日本語コミュニティフォーラムをどうぞ。';
        $ok = array(
            'CakePHPはPHP用の高速開発フレームワークです。アプリケーションの開発、メン',
            'テナンス、インストールのための拡張性の高い仕組みを提供します。 MVC や',
            'ORM といった、よく知られているデザインパターンを、「設定より規約優先」の',
            '考え方で利用して、CakePHPは開発コストや開発者が書く必要のあるコードを減ら',
            'します。',
			'',
            'CakePHPコミュニティでは、膨大な情報がやりとりされています。Google グルー',
            'プ（英語） には、質問やコメントを書き込める大きなフォーラムです。',
            '#cakephp on irc.freenode.netのIRCでも多くの情報が流れており、開発者や長年',
            'の利用者がそこにいます。 日本語情報であれば、日本語コミュニティフォーラム',
            'をどうぞ。',
            '',
        );

        $formatted = $AppEmailTest->wrap($text);
        $this->assertEqual($ok, $formatted);
    }

    /**
     *
     * @see http://blog.zista.jp/docs/id/0000000126
     */
    function test_encodeSettingInternalCharset() {

        //mb_internal_encoding('ISO-8859-1');
        //$this->Controller->charset = 'UTF-8';//'EmailTest'が抜けてる？
        $this->Controller->AppEmailTest->charset = 'ISO-2022-JP';

        $this->Controller->AppEmailTest->to = 'postmaster@localhost';
        $this->Controller->AppEmailTest->from = 'noreply@example.com';
        //$this->Controller->EmailTest->subject = 'هذه رسالة بعنوان طويل مرسل للمستلم';
        $this->Controller->AppEmailTest->subject = 'ＣＡＫＥは甘くて美味しい';
        $this->Controller->AppEmailTest->replyTo = 'noreply@example.com';
        $this->Controller->AppEmailTest->template = null;
        $this->Controller->AppEmailTest->delivery = 'debug';

        $this->Controller->AppEmailTest->sendAs = 'text';
        $this->assertTrue($this->Controller->AppEmailTest->send('This is the body of the message'));

        //$subject = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';
        $subject = '=?ISO-2022-JP?B?GyRCI0MjQSNLI0UkTzRFJC8kRkh+TCMkNyQkGyhC?=';

        preg_match('/Subject: (.*)Header:/s', $this->Controller->Session->read('Message.email.message'), $matches);

        $this->assertEqual(trim($matches[1]), $subject);

        //$result = mb_internal_encoding();
        //$this->assertEqual($result, 'ISO-8859-1');
    }

}