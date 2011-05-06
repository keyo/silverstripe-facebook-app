<?php
class FacebookTest extends SapphireTest {
	/**
	 * Cannot be run on localhost. If possible run from the domain set as your facebook applications website.
	 */
	function testUpdateStatus() {
		if($_SERVER['HTTP_HOST'] == 'localhost') {
			$this->fail('This test cannot be run on localhost. The facebook API requires the website be accessable from the internet');
		}
		FacebookModule::enable();
		$updated = FacebookModule::updateStatus(array('message' => 'test'.rand(0,999)));
		$this->assertTrue($updated);

	}
	/**
     * @expectedException InvalidArgumentException
     */
	function testNoConfigException() {
		FacebookModule::set_application_id('');
		FacebookModule::set_app_secret('');
		FacebookModule::set_api_key('');
		FacebookModule::enable();
		// live page page id
		FacebookModule::set_page_id('');

		//should throw an exception
		$this->setExpectedException('Exception');
		FacebookModule::updateStatus(array('messsage' => 'bla'));

	}

	function testDisabled() {
		FacebookModule::disable();
		$this->assertFalse(FacebookModule::updateStatus(array('message' => 'bla')));
	}
}