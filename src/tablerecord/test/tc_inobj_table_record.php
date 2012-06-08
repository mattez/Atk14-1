<?php
class tc_inobj_table_record extends tc_base{

	function _test_vytvareni_zaznamu(){
		global $dbmole;

		$this->_vyprazdni_testovaci_tabulku();

		$record = inobj_TestTable::CreateNewRecord(array());

		$this->assertEquals("test_table_id_seq",$record->getSequenceName());
		$this->assertEquals($dbmole->SelectSingleValue("SELECT CURRVAL('test_table_id_seq')","integer"),$record->getId());
		$this->assertNull($record->getValue("title"));
		$this->assertNull($record->getValue("price"));
		$this->assertNull($record->getValue("an_integer"));

		$record = inobj_TestTable::CreateNewRecord(array(
			"title" => "test",
			"price" => null,
			"an_integer" => 10
		));
		$this->assertEquals($dbmole->SelectSingleValue("SELECT CURRVAL('test_table_id_seq')","integer"),$record->getId());
		$this->assertEquals("test",$record->getValue("title"));
		$this->assertNull($record->getValue("price"));
		$this->assertEquals(10,$record->getValue("an_integer"));
	}

	function test_inobj_test_table(){
		global $dbmole;

		$this->_vyprazdni_testovaci_tabulku();

		$dbmole->doQuery("
			INSERT INTO test_table (
				id,
				title,
				an_integer,
				price,
				text,
				create_date,
				create_time,
				flag
			) VALUES(
				2,
				'titulek',
				21,
				17.0,
				'textik',
				'2001-12-12 12:00:00',
				'2001-12-12 12:00:00',
				TRUE
			)
		");

		//var_dump($dbmole->selectRows("SELECT * FROM test_table"));

		$record = inobj_TestTable::GetInstanceById(2);

		//var_dump($record->toArray()); exit;

		$this->assertEquals(2,$record->getId());
		$this->assertTrue(is_int($record->getId()));
		$this->assertEquals("inobj_TestTable#2",$record->toString());
		$this->assertEquals("inobj_TestTable#2","$record");

		$this->assertEquals("titulek",$record->getValue("title"));

		$this->assertEquals(21,$record->getValue("an_integer"));
		$this->assertTrue(is_int($record->getValue("an_integer")));

		$this->assertEquals(17.0,$record->getValue("price"));
		$this->assertTrue(is_float($record->getValue("price")));

		$this->assertEquals("textik",$record->getValue("text"));
		$this->assertEquals("2001-12-12",$record->getValue("create_date"));
		$this->assertEquals("2001-12-12 12:00:00",$record->getValue("create_time"));

		// testovani nastavoani vlastnosti
		// volani setValue()
		$this->assertTrue($record->setValue("price",15.6));
		$record = inobj_TestTable::GetInstanceById(2);
		$this->assertEquals(15.6,$record->getValue("price"));

		$this->assertTrue($record->setValue("price",null));
		$record = inobj_TestTable::GetInstanceById(2);
		$this->assertNull($record->getValue("price"));

		// volani setValues()
		$this->assertTrue($record->setValues(array("price" => 13.4,"an_integer" => 20)));
		$record = inobj_TestTable::GetInstanceById(2);
		$this->assertEquals(13.4,$record->getValue("price"));
		$this->assertEquals(20,$record->getValue("an_integer"));

		$this->assertTrue($record->setValues(array("price" => -12,"an_integer" => null)));
		$record = inobj_TestTable::GetInstanceById(2);
		$this->assertEquals(-12.0,$record->getValue("price"));
		$this->assertNull($record->getValue("an_integer"));

		$this->assertTrue($record->setValue("title","ahoj"));
		$record = inobj_TestTable::GetInstanceById(2);
		$this->assertEquals("ahoj",$record->getValue("title"));

		$this->assertTrue($record->setValue("title",""));
		$this->assertEquals("",$record->getValue("title"));
		$record = inobj_TestTable::GetInstanceById(2);
		$this->assertEquals("",$record->getValue("title"));

		// boolean
		$this->assertEquals(true,$record->getValue("flag"));
		$this->assertEquals('t',$dbmole->selectString("SELECT flag FROM test_table WHERE id=2"));

		$record->setValue("flag",false);
		$this->assertEquals(false,$record->getValue("flag"));
		$this->assertEquals('f',$dbmole->selectString("SELECT flag FROM test_table WHERE id=2"));

		$record->setValue("flag",null);
		$this->assertEquals(null,$record->getValue("flag"));
		$this->assertEquals(null,$dbmole->selectString("SELECT flag FROM test_table WHERE id=2"));
	}

	function test_validates_updating_of_fields(){
		global $dbmole;

		// vsechno, co menime, bude meneno
		$record = inobj_TestTable::CreateNewRecord(array(
			"title" => "nazev",
			"price" => 100,
			"an_integer" => 200
		));
		$record->setValues(array(
			"title" => "novy nazev",
			"price" => 101,
			"an_integer" => 201
		),array(
			"validates_updating_of_fields" => array("title","price","an_integer")
		));
		$this->assertEquals("novy nazev",$record->getValue("title"));
		$this->assertEquals(101.0,$record->getValue("price"));
		$this->assertEquals(201,$record->getValue("an_integer"));

		// zde se zmeni pouze 2 pole
		$record = inobj_TestTable::CreateNewRecord(array(
			"title" => "nazev",
			"price" => 100,
			"an_integer" => 200
		));
		$record->setValues(array(
			"title" => "novy nazev",
			"price" => 101,
			"an_integer" => 201
		),array(
			"validates_updating_of_fields" => array("title","price")
		));
		$this->assertEquals("novy nazev",$record->getValue("title"));
		$this->assertEquals((float)101,$record->getValue("price"));
		$this->assertEquals(200,$record->getValue("an_integer"));

		// zde se nic nesmi zmenit
		$record = inobj_TestTable::CreateNewRecord(array(
			"title" => "nazev",
			"price" => 100,
			"an_integer" => 200
		));
		$record->setValues(array(
			"title" => "novy nazev",
			"price" => 101,
			"an_integer" => 201
		),array(
			"validates_updating_of_fields" => array("text","create_date")
		));
		$this->assertEquals("nazev",$record->getValue("title"));
		$this->assertEquals(100.0,$record->getValue("price"));
		$this->assertEquals(200,$record->getValue("an_integer"));

		// nastaveni null hodnot
		$record = inobj_TestTable::CreateNewRecord(array(
			"title" => "nazev",
			"price" => 100,
			"an_integer" => 200
		));
		$record->setValues(array(
			"title" => null,
			"price" => null,
			"an_integer" => null
		),array(
			"validates_updating_of_fields" => array("title","an_integer")
		));
		$this->assertEquals(null,$record->getValue("title"));
		$this->assertEquals(100.0,$record->getValue("price"));
		$this->assertEquals(null,$record->getValue("an_integer"));
	}

	function test_validates_inserting_of_fields(){
		global $dbmole;

		$record = inobj_TestTable::CreateNewRecord(array(
			"title" => "nazev",
			"price" => 100,
			"an_integer" => 200
		),array(
			"validates_inserting_of_fields" => array("title","price","an_integer"),
		));
		$this->assertEquals("nazev",$record->getValue("title"));
		$this->assertEquals(100.0,$record->getValue("price"));
		$this->assertEquals(200,$record->getValue("an_integer"));

		$record = inobj_TestTable::CreateNewRecord(array(
			"title" => "nazev",
			"price" => 100,
			"an_integer" => 200
		),array(
			"validates_inserting_of_fields" => array("title"),
		));
		$this->assertEquals("nazev",$record->getValue("title"));
		$this->assertEquals(null,$record->getValue("price"));
		$this->assertEquals(null,$record->getValue("an_integer"));

		$record = inobj_TestTable::CreateNewRecord(array(
			"title" => "nazev",
			"price" => 100,
			"an_integer" => 200
		),array(
			"validates_inserting_of_fields" => array("create_date","text"),
		));
		$this->assertEquals(null,$record->getValue("title"));
		$this->assertEquals(null,$record->getValue("price"));
		$this->assertEquals(null,$record->getValue("an_integer"));

		$record = inobj_TestTable::CreateNewRecord(array(
			"text" => "texticek",
		),array(
			"validates_inserting_of_fields" => array("text"),
		));
		$this->assertEquals("texticek",$record->getValue("text"));

		$record = inobj_TestTable::CreateNewRecord(array(
			"text" => "texticek",
		),array(
			"validates_inserting_of_fields" => array("an_integer"),
		));
		$this->assertEquals(null,$record->getValue("text"));
	}

	function test_do_not_escape(){
		global $dbmole;

		$now = $dbmole->SelectSingleValue("SELECT CAST(NOW() AS DATE)");
		$before_2_days = $dbmole->SelectSingleValue("SELECT CAST((NOW() - INTERVAL '2 days') AS DATE)");

		$record = inobj_TestTable::CreateNewRecord(array(
			"create_date" => $now
		));

		$record->setValue("create_date",null);
		$this->assertNull($record->getValue("create_date"));

		$record->setValue("create_date","NOW()",array("do_not_escape" => true));
		$this->assertEquals($now,$record->getValue("create_date"));

		$record->setValue("create_date",null);

		$record->setValues(array("create_date" => "NOW()"),array("do_not_escape" => array("create_date")));
		$this->assertEquals($now,$record->getValue("create_date"));

		$record->setValues(array("create_date" => "NULL"),array("do_not_escape" => "create_date"));
		$this->assertNull($record->getValue("create_date"));

		$record->setValues(array("create_date" => "NOW()"),array("do_not_escape" => "create_date"));
		$this->assertEquals($now,$record->getValue("create_date"));

		$record = inobj_TestTable::CreateNewRecord(array(
			"create_date" => "(NOW() - INTERVAL '2 days')"
		),array("do_not_escape" => array("create_date")));
		$this->assertEquals($before_2_days,$record->getValue("create_date"));

		$record = inobj_TestTable::CreateNewRecord(array(
			"create_date" => "(NOW() - INTERVAL '2 days')"
		),array("do_not_escape" => "create_date"));
		$this->assertEquals($before_2_days,$record->getValue("create_date"));
	}

	function test_vytvareni_vice_instanci_najednou(){
		global $dbmole;

		$this->_vyprazdni_testovaci_tabulku();

		$record1 = $this->_vytvor_testovaci_zaznam();
		$record2 = $this->_vytvor_testovaci_zaznam();

		$id1 = $record1->getId();
		$id2 = $record2->getId();

		$records = inobj_TestTable::GetInstanceById(array($id2,$id1));
		$this->assertType("array",$records);
		$this->assertEquals(2,sizeof($records));
		$this->assertEquals($id2,$records[0]->getId());
		$this->assertEquals($id1,$records[1]->getId());

		$records = inobj_TestTable::GetInstanceById(array($id1,-1000,$id2));
		$this->assertType("array",$records);
		$this->assertEquals(3,sizeof($records));
		$this->assertEquals($id1,$records[0]->getId());
		$this->assertNull($records[1]);
		$this->assertEquals($id2,$records[2]->getId());
	}

	function test_get_keys(){
		global $dbmole;

		$this->_vyprazdni_testovaci_tabulku();

		$record = $this->_vytvor_testovaci_zaznam();
		$keys = $record->getKeys();

		// overime, zde alespone jedno pole mame null...
		// protoze i nazev takoveho pole musi byt vracen...
		$this->assertTrue(is_null($record->getValue("cena")));

		//var_dump($keys); exit;
	
		$this->assertEquals(array(
			"id",
			"title",
			"znak",
			"an_integer",
			"price",
			"cena",
			"cena2",
			"text",
			"perex",
			"flag",
			"binary_data",
			"binary_data2",
			"create_date",
			"create_time",
		),$keys);
	}

	function test_find_all(){
		global $dbmole;

		$this->_vyprazdni_testovaci_tabulku();

		$spring = $this->_vytvor_testovaci_zaznam(array("title" => "Spring"));
		$summer = $this->_vytvor_testovaci_zaznam(array("title" => "Summer"));
		$fall = $this->_vytvor_testovaci_zaznam(array("title" => "Fall"));
		$winter = $this->_vytvor_testovaci_zaznam(array("title" => "Winter"));

		// nekolik zpusobu zapisu conditions...
		// ... napred nenajdeme nic
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title='Monday'")));
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title=:title", "bind_ar" => array(":title" => "Monday"))));
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title='Monday'"))));
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title='Monday'"), "bind_ar" => array(":title" => "Monday"))));
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title" => "Monday"))));
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title" => array("Monday","Tuesday")))));

		// .. pak budeme nalezat Fall
		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title='Fall'"));
		$this->_test_fall($recs);

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title=:title", "bind_ar" => array(":title" => "Fall")));
		$this->_test_fall($recs);

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title='Fall'")));
		$this->_test_fall($recs);

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title=:title"), "bind_ar" => array(":title" => "Fall")));
		$this->_test_fall($recs);

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title" => "Fall")));
		$this->_test_fall($recs);

		// testovani order_by
		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title!='Fall'", "order_by" => "title"));
		$this->assertEquals("SpringSummerWinter",$recs[0]->getTitle().$recs[1]->getTitle().$recs[2]->getTitle());

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title!='Fall'", "order_by" => "title DESC"));
		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title='Spring' OR title='Summer' OR title='Winter'", "order_by" => "title DESC"));
		$this->assertEquals("WinterSummerSpring",$recs[0]->getTitle().$recs[1]->getTitle().$recs[2]->getTitle());

		// vyhledavani null hodnoty...
		// ... napred title s null hodnotou nemame
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title IS NULL")));
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title IS NULL"))));
		$this->assertEquals(array(),TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title" => null))));
		
		// ... ted jej vytvorime
		$null = $this->_vytvor_testovaci_zaznam(array("title" => null));

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => "title IS NULL"));
		$this->_test_null($recs);

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title IS NULL")));
		$this->_test_null($recs);

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title" => null)));
		$this->_test_null($recs);

		// 
		$century = $this->_vytvor_testovaci_zaznam(array("title" => "Century", "text" => null));
		$century2 = $this->_vytvor_testovaci_zaznam(array("title" => "Century", "text" => "No code"));

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title" => "Century")));
		$this->assertEquals(2,sizeof($recs));
		// defaultni trideni je podle id
		$this->assertEquals($century->getId(),$recs[0]->getId());
		$this->assertEquals($century2->getId(),$recs[1]->getId());

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title" => "Century", "text" => null)));
		$this->assertEquals(1,sizeof($recs));
		$this->assertEquals($century->getId(),$recs[0]->getId());

		$century3 = $this->_vytvor_testovaci_zaznam(array("title" => "Another Century","text" => "Uknown"));

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title='Century' OR title='Another Century'")));
		$this->assertEquals(3,sizeof($recs));

		// vyhledavani pomoci `field_name` IN (values)
		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title" => array("Century","Another Century"))));
		$this->assertEquals(3,sizeof($recs));

		// ted testujeme to, ze poskladane query musi mit na prisl. mistech zavorky:
		// ... WHERE (title='Century' OR title='Another Century') AND (text IS NULL)
		// napred spatny dotaz
		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title='Century' OR title='Another Century' AND text IS NULL")));
		$this->assertTrue(sizeof($recs)!=1);
		// ted spravny dotaz
		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title='Century' OR title='Another Century'","text IS NULL")));
		$this->assertEquals(1,sizeof($recs));
		$this->assertEquals($century->getId(),$recs[0]->getId());

		$recs = TableRecord::FindAll(array("class_name" => "inobj_TestTable", "conditions" => array("title='Century' OR title='Another Century'","text IS NOT NULL"),"order_by" => "title"));
		$this->assertEquals(2,sizeof($recs));
		$this->assertEquals($century3->getId(),$recs[0]->getId());
		$this->assertEquals($century2->getId(),$recs[1]->getId());

		// alternativni nazvy hodnot v $options
		//	 class_name -> class
		//	 conditions -> condition
		//	 bind_ar -> bind
		//	 order_by -> order
		$recs = TableRecord::FindAll(array(
			"class" => "inobj_TestTable",
			"condition" => array("title=:title1 OR title=:title2","text IS NOT NULL"),
			"bind" => array(":title1" => 'Century', ":title2" => "Another Century"),
			"order" => "title",
		));
		$this->assertEquals(2,sizeof($recs));
		$this->assertEquals($century3->getId(),$recs[0]->getId());
		$this->assertEquals($century2->getId(),$recs[1]->getId());

		$rec = TableRecord::FindFirst(array(
			"class" => "inobj_TestTable",
			"condition" => array("title=:title1 OR title=:title2","text IS NOT NULL"),
			"bind" => array(":title1" => 'Century', ":title2" => "Another Century"),
			"order" => "title",
		));
		$this->assertEquals($century3->getId(),$rec->getId());
	}

	function test_serialize(){
		$rec = inobj_TestTable::CreateNewRecord(array(
			"title" => "Title",
			"price" => 123,
			"an_integer" => 2
		));

		$ser = serialize($rec);
		$this->assertTrue(!preg_match('/dbmole/',$ser)); // see inobj::__sleep()

		$rec2 = unserialize($ser);
		$this->assertEquals($rec->toArray(),$rec2->toArray());
	}

	function test_GetSequenceNextval(){
		$id1 = inobj_TestTable::GetSequenceNextval();
		$id2 = inobj_TestTable::GetSequenceNextval();

		$this->assertTrue(is_numeric($id1));
		$this->assertTrue(is_numeric($id2));

		$this->assertTrue($id2>$id1);

		$id1 = inobj_TestTable::GetNextId();
		$id2 = inobj_TestTable::GetNextId();

		$this->assertTrue(is_numeric($id1));
		$this->assertTrue(is_numeric($id2));

		$this->assertTrue($id2>$id1);
	}

	function _test_fall($recs){
		$this->assertEquals(1,sizeof($recs));
		$this->assertEquals("Fall",$recs[0]->getTitle());
	}

	function _test_null($recs){
		$this->assertEquals(1,sizeof($recs));
		$this->assertNull($recs[0]->getTitle());
	}

	function _vytvor_testovaci_zaznam($values = array()){
		$values = array_merge(array(
			"title" => "testovaci zaznam",
			"price" => 13.60,
			"an_integer" => 11
		),$values);
		return inobj_TestTable::CreateNewRecord($values);
	}


	function _vyprazdni_testovaci_tabulku(){
		global $dbmole;

		$dbmole->doQuery("DELETE FROM test_table");
	}
}
