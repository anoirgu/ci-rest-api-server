<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'core/Api_Controller.php';

class Formatted extends Api_Controller
{
	public function __construct(){
		parent::__construct();
		
		$segments = $this->uri->segment_array();
		$format = $this->input->get('format') ? $this->input->get('format') : end($segments);
		in_array($format, array('json','xml')) ? $this->_format($format) : $this->_format('json');
	}
	
	public function index(){
		
		$this->_response(array(
			'status' => 1,
			'payload' => array(
				'users' => array(
					array('id'=>1,'name'=>'Sujeet', 'age'=>25),
					array('id'=>2,'name'=>'Suraj', 'age'=>23),
					array('id'=>3,'name'=>'Sudhir', 'age'=>24)
				)
			)
		));
	}
}