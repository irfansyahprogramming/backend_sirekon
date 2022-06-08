<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_Model {
	
	public function _construct()
	{
		$this->load->database();
	}

	function ExcelToPHP($dateValue = 0) {
	    $myExcelBaseDate = 25569;
	    //  Adjust for the spurious 29-Feb-1900 (Day 60)
	    if ($dateValue < 60) {
	        --$myExcelBaseDate;
	    }

	    // Perform conversion
	    if ($dateValue >= 1) {
	        $utcDays = $dateValue - $myExcelBaseDate;
	        $returnValue = round($utcDays * 86400);
	        if (($returnValue <= PHP_INT_MAX) && ($returnValue >= -PHP_INT_MAX)) {
	            $returnValue = (integer) $returnValue;
	        }
	    } else {
	        $hours = round($dateValue * 24);
	        $mins = round($dateValue * 1440) - round($hours * 60);
	        $secs = round($dateValue * 86400) - round($hours * 3600) - round($mins * 60);
	        $returnValue = (integer) gmmktime($hours, $mins, $secs);
	    }

	    // Return
	    return $returnValue;
	}   //  function ExcelToPHP()

	function getCaptcha()
	{
		$id = rand(1,11);
		$this->db->where('id',$id);
        $this->db->FROM('ref_captcha');
        $query = $this->db->get(); 
        return $query;
	}

	
	function cekKebenaran($id,$in)
	{
		$this->db->where('id',$id);
        $this->db->FROM('ref_captcha');
        $query = $this->db->get();

        if ($query->num_rows() >= 1){
        	$rec = $query->row();
        	$ans = $rec->ans;

        	if ($ans == $in){
        		$status = 1;
        	}else{
        		$status = 0;
        	}
        }else{
        	$status = 0;
        }
        return $status;
	}

	function getMode($id)
	{
		if ($id <> 'all'){
			$this->db->where('id',$id);
			$this->db->FROM('master_otoritas');
        }else{
        	$this->db->FROM('master_otoritas');
        }
		
        $query = $this->db->get(); 
		return $query;
	}
	
	function getUsers($user)
	{
		if ($user <> 'all'){
			$this->db->where('userid',$user);
			$this->db->FROM('master_user');
        }else{
        	$this->db->FROM('master_user');
        }
		
        $query = $this->db->get(); 
		return $query;
	}

	function updateUser($data,$where)
	{
		$this->db->where($where);
		return $this->db->update('master_user',$data);
	}
	
	function simpanUser($data){
		$query = $this->db->insert('master_user',$data);
    	return $query;  
	}
	
	function deleteUser($where)
	{
		$this->db->where($where);
		$query = $this->db->delete('master_user');
		return $query;
	}
	
	function getMenu($id)
	{
		if ($id <> 'all'){
			$this->db->where('id',$id);
			$this->db->FROM('master_menu');
        }else{
        	$this->db->FROM('master_menu');
        }
		
        $query = $this->db->get(); 
		return $query;
	}
	
	function updateMenu($data,$where)
	{
		$this->db->where($where);
		return $this->db->update('master_menu',$data);
	}
	
	function simpanMenu($data){
		$query = $this->db->insert('master_menu',$data);
    	return $query;  
	}
	
	function deleteMenu($where)
	{
		$this->db->where($where);
		$query = $this->db->delete('master_menu');
		return $query;
	}

	function updateMode($data,$where)
	{
		$this->db->where($where);
		return $this->db->update('master_otoritas',$data);
	}
	
	function simpanMode($data){
		$query = $this->db->insert('master_otoritas',$data);
    	return $query;  
	}
	
	function deleteMode($where)
	{
		$this->db->where($where);
		$query = $this->db->delete('master_otoritas');
		return $query;
	}
	
	function getFileGlobal($id)
	{
		if ($id <> 'all'){
			$this->db->where('id',$id);
			$this->db->where('gd','G');
			$this->db->FROM('master_file');
        }else{
        	$this->db->where('gd','G');
			$this->db->FROM('master_file');
        }
		
        $query = $this->db->get(); 
		return $query;
	}
	
	function getFileDetil($id)
	{
		if ($id <> 'all'){
			$this->db->where('id',$id);
			$this->db->where('gd','D');
			$this->db->FROM('master_file');
        }else{
        	$this->db->where('gd','D');
			$this->db->FROM('master_file');
        }
		$this->db->order_by('id','desc');
        $query = $this->db->get(); 
		return $query;
	}

	function getFileTIK($id)
	{
		if ($id <> 'all'){
			$this->db->where('id',$id);
			$this->db->where('gd','T');
			$this->db->FROM('master_file');
        }else{
        	$this->db->where('gd','T');
			$this->db->FROM('master_file');
        }
		
        $query = $this->db->get(); 
		return $query;
	}

	function updateFileGlobal($data,$where)
	{
		$this->db->where($where);
		return $this->db->update('master_file',$data);
	}
	
	function ubahFileGlobal($id)
	{
		$sql = "update master_file set readFile='1' where id=".$id;
		$query = $this->db->query($sql);
		return $query;
	}

	function simpanFileGlobal($data){
		$query = $this->db->insert('master_file',$data);
    	return $query;  
	}
	
	function insertBook($data){
		$query = $this->db->insert('ref_book',$data);
    	return $query;  
	}
	
	function insertBookDetil($data){
		$query = $this->db->insert('ref_book_detil',$data);
    	return $query;  
	}
	public function insert_multiple($data){
    	$this->db->insert_batch('ref_book_detil', $data);
  	}
	function deleteFileGlobal($where)
	{
		$this->db->where($where);
		$query = $this->db->delete('master_file');
		return $query;
	}

	function getbookGlobal($bank)
	{
		$this->db->where('bank',$bank);
		if ($bank == 'bni'){
			$this->db->like('description', '0012949353 SPC UNJ H2H', 'both');
		}elseif ($bank == 'bukopin'){
			$this->db->where('branch', '1654');
		}else{}
		
		$this->db->FROM('ref_book');
        $this->db->order_by('id', 'DESC');
		
        $query = $this->db->get(); 
		return $query;
	}

	function getRecFileGlobal($where)
	{
		
		if ($where['bank'] == 'bni'){
			$des = " description like '%0012949353 SPC UNJ H2H%' and ";
		}elseif ($where['bank'] == 'bkp'){
			$des = " branch ='1654' and ";
		}else{
			$des = "";
		}
		/*
		$this->db->FROM('ref_book');
        $this->db->order_by('id', 'DESC');
		$query = $this->db->get(); 
		*/
		$sql = "select * from ref_book where ".$des." bank = '".$where['bank']."' and date(post_date) >= '".$where['awal']."' and date(post_date) <= '".$where['akhir']."' order by date(post_date)";
		$query = $this->db->query($sql);
		return $query;
	}
	
	function getRecFileDetil($id)
	{
		$sql = "select date(post_date) as tglbayar, count(nim) as jmlbayar, sum(credit) as totalbayar from ref_book_detil where id_book='".$id."' and non_ukt='0'";
		$query = $this->db->query($sql);
		return $query;
	}

	function getIdbook($id_book)
	{
		$this->db->where('id',$id_book);
		$this->db->FROM('ref_book');
        $query = $this->db->get(); 
		return $query;
	}
	
	function getIdDetbook($id)
	{
		$this->db->where('id',$id);
		$this->db->FROM('ref_book_detil');
        $query = $this->db->get(); 
		return $query;
	}

	function getBookTIK($id)
	{
		$this->db->where('idTagihan',$id);
		$this->db->FROM('ref_book_tik');
        $query = $this->db->get(); 
		return $query;
	}
	function updateBookTIK($where,$data)
	{
		$this->db->where($where);
		return $this->db->update('ref_book_tik',$data);
	}
	
	function upNonUkt($data,$id)
	{
		$this->db->where('id',$id);
		return $this->db->update('ref_book_detil',$data);
	}
	
	function insertBookTIK($data){
		$query = $this->db->insert('ref_book_tik',$data);
    	return $query;  
	}

	function getRecFileDetilByDate($data)
	{
		$this->db->where('non_ukt','0');
		$this->db->where('bank',$data['bank']);
		$this->db->where('date_pay',$data['tanggal']);
		$this->db->FROM('ref_book_detil');
        $query = $this->db->get(); 
		return $query;
	}
	
	function getRecFileDetilByDateRange($data)
	{
		/*
		$this->db->where('non_ukt','0');
		$this->db->where('bank',$data['bank']);
		$this->db->where('date_pay >=',$data['tanggal0']);
		$this->db->where('date_pay <=',$data['tanggal1']);
		$this->db->FROM('ref_book_detil');
        $query = $this->db->get(); 
		return $query;
		*/
		$sql = "select * from ref_book_detil where non_ukt = '0' and bank='".$data['bank']."' and date_pay >= '".$data['tanggal0']."' and date_pay <= '".$data['tanggal1']."'";
		//echo $sql;
		$query = $this->db->query($sql);
		return $query;
	}

	function getRecFileTIKByDateRange($data)
	{
		$sql = "select * from ref_book_tik where kodeBank='".strtoupper($data['bank'])."' and date(tanggalPembayaran) >= '".$data['tanggal0']."' and date(tanggalPembayaran) <= '".$data['tanggal1']."'";
		//echo $sql;
		$query = $this->db->query($sql);
		return $query;
	}

	function getJmlFileDetilByDate($data)
	{
		$sql = "select count(nim) as jmlbayar, sum(credit) as nominalbayar from ref_book_detil where non_ukt = '0' and bank='".$data['bank']."' and date_pay = '".$data['tanggal']."'";
		$query = $this->db->query($sql);
		return $query;
	}

	function getRecFileTIKByDate($data)
	{
		$this->db->where('kodeBank',$data['bank']);
		$this->db->like('tanggalPembayaran',$data['tanggal'],'both');
		$this->db->FROM('ref_book_tik');
        $query = $this->db->get(); 
		return $query;
	}

	function getJmlFileTIKByDate($tanggal,$bank)
	{
		$sql = "select count(nomorPembayaran) as jmlbayar, sum(totalPembayaran) as nominalbayar from ref_book_tik where date(tanggalPembayaran) = '".$tanggal."' and kodeBank='".$bank."'";
		$query = $this->db->query($sql);
		return $query;
	}

	function encryptPass ($pass)
	{
		$out = base64_encode(base64_encode(base64_encode(base64_encode(base64_encode($pass)))));
		return $out;
	}

	function decryptPass ($pass)
	{
		$out = base64_decode(base64_decode(base64_decode(base64_decode(base64_decode($pass)))));
		return $out;
	}

	function callAPI($method, $url, $data){
	   	$curl = curl_init();

	   	switch ($method){
	      	case "POST":
	         	curl_setopt($curl, CURLOPT_POST, 1);
	         	if ($data)
	            	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	         		break;
	      	case "PUT":
	        	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
	         	if ($data)
	        	    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
	         		break;
	      	default:
	         	if ($data)
	            	$url = sprintf("%s?%s", $url, http_build_query($data));
	   	}

	   	// OPTIONS:
	   	curl_setopt($curl, CURLOPT_URL, $url);
	   	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	      	//'APIKEY: 111111111111111111111',
	      	'Content-Type: application/json',
	   	));
	   	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	   	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

	   	// EXECUTE:
	   	$result = curl_exec($curl);
	   	if(!$result){die("Connection Failure");}
	   		curl_close($curl);
	   	return $result;
	}

}
?>