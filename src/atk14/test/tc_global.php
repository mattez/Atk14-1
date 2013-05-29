<?php
class TcGlobal extends TcBase{
	function test(){
		$global = new Atk14Global();

		$this->assertEquals("cs",$global->getDefaultLang());
		$this->assertEquals("cs",$global->getLang());

		$global->setValue("lang","en");

		$this->assertEquals("cs",$global->getDefaultLang());
		$this->assertEquals("en",$global->getLang());
	}

	function test_getConfig(){
		$global = new Atk14Global();

		$this->assertEquals(array(
			"name" => "Magic Plugin",
			"purpose" => "Testing"
		),$global->getConfig("magic_plugin"));

		$this->assertEquals(null,$global->getConfig("not_existing_config"));
	}
}
