<?php 
if (!defined('BASEPATH')) exit('No direct script access allowed'); 
	require_once APPPATH."/third_party/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php";
	class Excel_factory extends PHPExcel {
		public function __construct() {
		parent::__construct();
	}
}