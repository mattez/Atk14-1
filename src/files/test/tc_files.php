<?php
class TcFiles extends TcBase{
	function test_get_file_content(){
		$content = Files::GetFileContent("test.txt",$err,$err_str);
		$this->assertFalse($err);
		$this->assertEquals("Hello from the Earth!\n",$content); // nechapu ten \n

		$content = Files::GetFileContent("empty_file.txt",$err,$err_str);
		$this->assertFalse($err);
		$this->assertEquals("",$content);

		$content = Files::GetFileContent("non_existing_file.txt",$err,$err_str);
		$this->assertTrue($err);
		$this->assertEquals("non_existing_file.txt is not a file",$err_str);

	}

	function test_get_image_size(){
		$hlava = Files::GetFileContent("hlava.jpg",$err,$err_str);
		$this->assertEquals(68423,strlen($hlava));
		$this->assertEquals("93724a4b921ddaf8582bbcb7f2077034",md5($hlava));
		list($width,$height) = Files::GetImageSize($hlava,$err,$err_str);
		$this->assertEquals(325,$width);
		$this->assertEquals(448,$height);

		$hlava = "xxxxxxxxxxxxxxxxx";
		$this->assertNull(Files::GetImageSize($hlava,$err,$err_str));
	}

	function test_deterine_file_type(){
		$this->assertEquals("image/jpeg",Files::DetermineFileType("hlava.jpg"));
		$this->assertEquals("text/plain",Files::DetermineFileType("test.txt"));
	}

	function test_write_to_temp(){
		$content = Files::GetFileContent("hlava.jpg");
		$tmp_filename = Files::WriteToTemp($content);
		$this->assertTrue(file_exists($tmp_filename));
		$this->assertNotContains("hlava.jpg",$tmp_filename);
		$tmp_content = Files::GetFileContent($tmp_filename);
		$this->assertEquals($content,$tmp_content);

		$tmp_filename2 = Files::WriteToTemp($content);
		$this->assertTrue(file_exists($tmp_filename2));
		$this->assertTrue($tmp_filename!=$tmp_filename2);

		Files::Unlink($tmp_filename);
		Files::Unlink($tmp_filename2);
	}

	function test_get_temp_filename(){
		$t1 = Files::GetTempFilename();
		$t2 = Files::GetTempFilename();

		$this->assertContains(TEMP,$t1);
		$this->assertContains(TEMP,$t2);

		$this->assertTrue($t1!=$t2);
	}

	function test_move_file(){
		$dir1 = TEMP."/dir_1/";
		$dir2 = TEMP."/dir_2";
		$dir3 = TEMP."/dir_3";

		if(file_exists($dir1)){ rmdir($dir1); }
		if(file_exists($dir2)){ rmdir($dir2); }
		if(file_exists($dir3)){ rmdir($dir3); }

		mkdir($dir1);
		mkdir($dir3);
		
		$this->assertEquals(true,file_exists($dir1));
		$this->assertEquals(false,file_exists($dir2));

		Files::MoveFile($dir1,$dir2);

		$this->assertEquals(false,file_exists($dir1));
		$this->assertEquals(true,file_exists($dir2) && is_dir($dir2));

		touch("$dir2/a_file.txt");

		$this->assertEquals(true,file_exists("$dir2/a_file.txt"));
		$this->assertEquals(false,file_exists("$dir2/another_file.txt"));
		
		Files::MoveFile("$dir2/a_file.txt","$dir2/another_file.txt");

		$this->assertEquals(false,file_exists("$dir2/a_file.txt"));
		$this->assertEquals(true,file_exists("$dir2/another_file.txt"));

		// moving from a directory to another directory
		Files::MoveFile("$dir2/another_file.txt","$dir3");

		$this->assertEquals(false,file_exists("$dir2/another_file.txt"));
		$this->assertEquals(true,file_exists("$dir3/another_file.txt"));

		unlink("$dir3/another_file.txt");

		// moving directory
		Files::MoveFile("$dir3","$dir2/");

		$this->assertEquals(false,file_exists($dir3));
		$this->assertEquals(true,file_exists("$dir2/dir_3/"));

		rmdir("$dir2/dir_3");
	}

	function test_AppendToFile(){
		$filename = Files::GetTempFilename();
		$this->assertEquals(false,file_exists($filename));

		$this->assertEquals(5,Files::WriteToFile($filename,"Hello"));
		$this->assertEquals("Hello",Files::GetFileContent($filename));

		$this->assertEquals(7,Files::AppendToFile($filename," World!"));
		clearstatcache();
		$this->assertEquals("Hello World!",Files::GetFileContent($filename));

		Files::Unlink($filename);
	}

	function test__NormalizeFilename(){
		foreach(array(
			"/path/to/file" => "/path/to/file",
			"path/to/dir/" => "path/to/dir/",
			"../path/to/file" => "../path/to/file",
			"/path/to/file" => "/path/to/file",
			"/../path/to/file" => "/../path/to/file", // in fact this is a nonsense

			"////path///to//file" => "/path/to/file",
			"////path///to//dir///" => "/path/to/dir/",
			"/path/to//../tmp/images/..//attachments/" => "/path/tmp/attachments/",
			"/path/to//..///../tmp/images/..//attachments/" => "/tmp/attachments/",
		) as $filename => $normalized){
			$this->assertEquals($normalized,Files::_NormalizeFilename($filename));
		}
	}

	function test_Mkdir(){
		if(file_exists($_d = TEMP . "/ddd/d3")){ rmdir($_d); }
		if(file_exists($_d = TEMP . "/ddd")){ rmdir($_d); }

		$dir = TEMP . "/ddd/../ddd/d3";
		$this->assertFalse(file_exists($dir));

		$this->assertEquals(1,Files::Mkdir($dir)); // in fact 2 should be returned (2 directories were created)
		$this->assertEquals(0,Files::Mkdir($dir));
		$this->assertTrue(file_exists($dir));

		rmdir(TEMP."/ddd/d3");
		rmdir(TEMP."/ddd");
	}

	function test_DetermineFileType(){

		// http://en.wikipedia.org/wiki/Internet_media_type

		$this->assertEquals(null,Files::DetermineFileType("non_existing_file.dat"));
		
		foreach(array(
			"jpg" => "image/jpeg",
			"png" => "image/png",
			"png" => "image/png",
			"gif" => "image/gif",
			"tiff" => "image/tiff",
			"bmp" => "image/bmp",

			"eps" => "application/postscript",
			"ai" => "application/postscript",
			"svg" => "image/svg+xml",

			"odt" => "application/vnd.oasis.opendocument.text",
			"ods" => "application/vnd.oasis.opendocument.spreadsheet",

			"xls" => "application/vnd.ms-excel",
			"doc" => "application/msword",
			"ppt" => "application/vnd.ms-powerpoint",
			"xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",

			"csv" => "text/csv",

			"zip" => "application/zip",

			"docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",

			"mp3" => "audio/mpeg",
 		) as $file => $mime_type){
			$file = __DIR__."/sample_files/sample.$file";
			$this->assertEquals($mime_type,Files::DetermineFileType($file),$file);
		}
	}

	function test_RecursiveUnlinkDir(){
		$this->assertEquals(false,file_exists(TEMP . "/to_be_deleted"));

		mkdir(TEMP . "/to_be_deleted");
		copy(__FILE__ , TEMP."/to_be_deleted/file1");
		copy(__FILE__ , TEMP."/to_be_deleted/file2");
		mkdir(TEMP . "/to_be_deleted/0");
		copy(__FILE__ , TEMP."/to_be_deleted/0/file3");

		$this->assertEquals(true,file_exists(TEMP."/to_be_deleted/file1"));
		$this->assertEquals(true,file_exists(TEMP."/to_be_deleted/file2"));

		$files_deleted = Files::RecursiveUnlinkDir(TEMP . "/to_be_deleted");
		$this->assertEquals(5,$files_deleted);
	}

	function test_FindFiles(){
		$files = Files::FindFiles("sample_files/");
		$this->assertTrue(sizeof($files)>10);
		$this->assertTrue(in_array('sample_files/sample.jpg',$files));

		// --- maxdepth

		$files = Files::FindFiles("sample_files/",array("maxdepth" => 0));
		$this->assertEquals(array(),$files);

		$files = Files::FindFiles("sample_files/",array("maxdepth" => -1));
		$this->assertEquals(array(),$files);

		$files = Files::FindFiles("sample_files/",array("maxdepth" => 1));
		$this->assertTrue(sizeof($files)>10);
		$this->assertTrue(in_array('sample_files/sample.jpg',$files));

		$files = Files::FindFiles("./");
		$files_maxdepth_limited = Files::FindFiles("./",array("maxdepth" => 1));
		$this->assertTrue(sizeof($files)>sizeof($files_maxdepth_limited));
		$this->assertTrue(in_array('./tc_files.php',$files));
		$this->assertTrue(in_array('./sample_files/sample.jpg',$files));
		$this->assertTrue(in_array('./tc_files.php',$files_maxdepth_limited));
		$this->assertFalse(in_array('./sample_files/sample.jpg',$files_maxdepth_limited));

		// --- pattern

		$files = Files::FindFiles("./sample_files/",array(
			"pattern" => '/^sample\.(p..|jpg)$/'
		));
		$this->assertEquals(array(
			'./sample_files/sample.jpg',
			'./sample_files/sample.pdf',
			'./sample_files/sample.png',
			'./sample_files/sample.ppt',
		),$files);

		// --- invert_pattern

		$files = Files::FindFiles("./sample_files/",array(
			"pattern" => '/^sample\.(p..|jpg)$/',
			"invert_pattern" => '/\.ppt/'
		));
		$this->assertEquals(array(
			'./sample_files/sample.jpg',
			'./sample_files/sample.pdf',
			'./sample_files/sample.png',
		),$files);

		// --- hidden files are included

		touch('temp/.hidden_file.txt');

		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.txt$/'
		));
		$this->assertEquals(array('temp/.hidden_file.txt'),$files);

		$files = Files::FindFiles("temp/");
		$this->assertTrue(in_array('temp/.hidden_file.txt',$files));

		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.txt$/',
			"invert_pattern" => '/^\./',
		));
		$this->assertEquals(array(),$files);
		
		unlink('temp/.hidden_file.txt');


		// ----

		touch('temp/application.log',time() - 60);

		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/'
		));
		$this->assertEquals(array('temp/application.log'),$files);

		// min_mtime
		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/',
			"min_mtime" => time() - 30
		));
		$this->assertEquals(array(),$files);
		//
		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/',
			"min_mtime" => time() - 60
		));
		$this->assertEquals(array('temp/application.log'),$files);
		// 
		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/',
			"min_mtime" => time() - 120
		));
		$this->assertEquals(array('temp/application.log'),$files);

		// max_mtime
		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/',
			"max_mtime" => time() - 120
		));
		$this->assertEquals(array(),$files);
		//
		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/',
			"max_mtime" => time() - 60
		));
		$this->assertEquals(array('temp/application.log'),$files);
		//
		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/',
			"max_mtime" => time() - 30
		));
		$this->assertEquals(array('temp/application.log'),$files);

		// both min_mtime & max_mtime
		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/',
			"min_mtime" => time() - 120,
			"max_mtime" => time() - 30
		));
		$this->assertEquals(array('temp/application.log'),$files);
		//
		$files = Files::FindFiles("temp/",array(
			"pattern" => '/^.*\.log$/',
			"min_mtime" => time() - 180,
			"max_mtime" => time() - 120
		));
		$this->assertEquals(array(),$files);

		unlink("temp/application.log");
	}
}
