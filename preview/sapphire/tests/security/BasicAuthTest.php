<?php
/**
 * @package sapphire
 * @subpackage tests
 */

class BasicAuthTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/security/BasicAuthTest.yml';
	
	function tearDown() {
		parent::tearDown();
		
		BasicAuth::protect_entire_site(false);
	}

	function testBasicAuthEnabledWithoutLogin() {
		$origUser = @$_SERVER['PHP_AUTH_USER'];
		$origPw = @$_SERVER['PHP_AUTH_PW'];
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
		
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(401, $response->getStatusCode());
		
		$_SERVER['PHP_AUTH_USER'] = $origUser;
		$_SERVER['PHP_AUTH_PW'] = $origPw;
	}
	
	function testBasicAuthDoesntCallActionOrFurtherInitOnAuthFailure() {
		$origUser = @$_SERVER['PHP_AUTH_USER'];
		$origPw = @$_SERVER['PHP_AUTH_PW'];
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertFalse(BasicAuthTest_ControllerSecuredWithPermission::$index_called);
		$this->assertFalse(BasicAuthTest_ControllerSecuredWithPermission::$post_init_called);
		
		$_SERVER['PHP_AUTH_USER'] = 'user-in-mygroup@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertTrue(BasicAuthTest_ControllerSecuredWithPermission::$index_called);
		$this->assertTrue(BasicAuthTest_ControllerSecuredWithPermission::$post_init_called);
		
		$_SERVER['PHP_AUTH_USER'] = $origUser;
		$_SERVER['PHP_AUTH_PW'] = $origPw;
	}

	function testBasicAuthEnabledWithPermission() {
		$origUser = @$_SERVER['PHP_AUTH_USER'];
		$origPw = @$_SERVER['PHP_AUTH_PW'];
		
		$_SERVER['PHP_AUTH_USER'] = 'user-in-mygroup@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'wrongpassword';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(401, $response->getStatusCode(), 'Invalid users dont have access');
		
		$_SERVER['PHP_AUTH_USER'] = 'user-without-groups@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(401, $response->getStatusCode(), 'Valid user without required permission has no access');
		
		$_SERVER['PHP_AUTH_USER'] = 'user-in-mygroup@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(200, $response->getStatusCode(), 'Valid user with required permission has access');
		
		$_SERVER['PHP_AUTH_USER'] = $origUser;
		$_SERVER['PHP_AUTH_PW'] = $origPw;
	}
	
	function testBasicAuthEnabledWithoutPermission() {
		$origUser = @$_SERVER['PHP_AUTH_USER'];
		$origPw = @$_SERVER['PHP_AUTH_PW'];
		
		$_SERVER['PHP_AUTH_USER'] = 'user-without-groups@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'wrongpassword';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$this->assertEquals(401, $response->getStatusCode(), 'Invalid users dont have access');
		
		$_SERVER['PHP_AUTH_USER'] = 'user-without-groups@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$this->assertEquals(200, $response->getStatusCode(), 'All valid users have access');
		
		$_SERVER['PHP_AUTH_USER'] = 'user-in-mygroup@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$this->assertEquals(200, $response->getStatusCode(), 'All valid users have access');
		
		$_SERVER['PHP_AUTH_USER'] = $origUser;
		$_SERVER['PHP_AUTH_PW'] = $origPw;
	}
	
}

class BasicAuthTest_ControllerSecuredWithPermission extends ContentController implements TestOnly {
	
	static $post_init_called = false;
	
	static $index_called = false;
	
	function init() {
		self::$post_init_called = false;
		self::$index_called = false;
		
		BasicAuth::protect_entire_site(true, 'MYCODE');
		
		parent::init();
		
		self::$post_init_called = true;
	}
	
	function index() {
		self::$index_called = true;
	}
	
}

class BasicAuthTest_ControllerSecuredWithoutPermission extends ContentController implements TestOnly {
	
	function init() {
		BasicAuth::protect_entire_site(true, null);
		
		parent::init();
	}
	
}