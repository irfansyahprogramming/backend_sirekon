<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class PengelolaanDB extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key

        $this->load->model('api_model');//Model Function
        $this->load->library('encrypt');
        $this->load->library('Excel_factory');
		
    }
	function _getServer (){
		$server_url = "../../../../rest_rekon/";
		return $server_url;
	}
    //Get Captcha
	public function captcha_get(){ 
		//echo $this->uri->segment(3);
		$dataCapctha = array();
		//ambil data di model
		$fetch_row = $this->api_model->getCaptcha();
		if ($fetch_row->num_rows() >= 1){
			$dataCapctha["status"] = true;
			$dataCapctha["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			foreach($fetch_row->result() as $row){
				$no++;
				
				$data_no = array ();
				$data_no["id"] = $row->id;
				$data_no["quest"] = $row->quest;
				$data_no["ans"] = $row->ans;
				$dataCapctha["data"]= $data_no;
			}
			
		}else{
			$dataCapctha["status"] = false;
			$dataCapctha["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataCapctha);
	}

	public function login_post(){ 
		$data = array ();
		
		$server = $this->_getServer();
		
		$user = $this->input->post('username');
		$pass = $this->input->post('password');
		$idCap = $this->input->post('captcha_id');
		$isiCap = $this->input->post('captcha');

		//cek captcha
		$getCaptcha = $this->api_model->cekKebenaran($idCap,$isiCap);
		if ($getCaptcha >= 1){

			$getData = $this->api_model->getUsers($user);
			if ($getData->num_rows() >= 1){
				$recUser = $getData->row();
				$passDB = $recUser->password;

				$decPassDB = $this->api_model->decryptPass($passDB);
				if (trim($pass) == $decPassDB){
					$sess = $arrayName = array(
						'username' 	=> $recUser->userid,
						'nama'	 	=> $recUser->nama,
						'email'		=> $recUser->email,
						'mode'		=> $recUser->mode,
						'foto' 		=> ($recUser->foto<>'')?$server.$recUser->foto:$server."akun/user.jpg",
						'kelamin'	=> $recUser->kelamin,
						'status' 	=> true, 
	            		'message' 	=> 'Berhasil'
					);

					$this->session->set_userdata($sess);
					$this->set_response($message, REST_Controller::HTTP_CREATED);

				}else{
					$this->response([
		                'status' => FALSE,
		                'message' => 'No users were found'
		            ], REST_Controller::HTTP_NOT_FOUND);
				}
			}

		}else{
			$this->response([
                'status' => FALSE,
                'message' => 'No users were found'
            ], REST_Controller::HTTP_NOT_FOUND);
		}

	}
	
	public function users_get()
    {
		$server = $this->_getServer();
		$user = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$getData = $this->api_model->getUsers($user);
		if ($getData->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			$data_no = '';
			foreach($getData->result() as $row){
				$no++;
				
				$data_no = array ();
				
				$userid = $row->userid;
				$mode = $row->mode;
				$password = $row->password;
				$nama = $row->nama;
				$email = $row->email;
				$foto = ($row->foto<>'')?$server.$row->foto:$server."akun/user.jpg";
				$kelamin = ($row->kelamin="L")?'Laki-laki':'Perempuan';

				$getMode = $this->api_model->getMode($mode);
				if ($getMode->num_rows() >=1){
					$nmode = $getMode->row()->auth;
				}else{
					$nmode = "Mode Kode ".$mode." Tidak Diketahui Otoritasnya";
				}
				$aksi = "
					<button id=\"btnEdit\" class=\"btn btn-primary\" onclick=\"editUser('".$userid."')\"><i class=\"fa fa-pencil\"> </i>Edit</button>
					<button id=\"btnEdit\" class=\"btn btn-danger\" onclick=\"deleteUser('".$userid."')\"><i class=\"fa fa-trash\"></i> Hapus</button>";
				
				$data_no [] = $userid;
				$data_no [] = $nama;
				$data_no [] = $email;
				$data_no [] = $kelamin;
				$data_no [] = $nmode;
				$data_no [] = "<img src=\"".$foto."\" style=\"width:50px;height:50px;\">";
				$data_no [] = $aksi;

				$dataArray["data"][]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
    }

    public function users_post()
    {
		$user = $this->post('username');
		$password = $this->api_model->encryptPass($this->post('password'));
		$nama = $this->post('nama');
		$email = $this->post('email');
		$foto = $this->post('foto');
		$kelamin = $this->post('kelamin');
		$mode = $this->post('mode');
		

		if (isset($_FILES["fileGambar"]["tmp_name"])){
			$temp_gambar = $_FILES["fileGambar"]["tmp_name"];
			$name_gambar = $_FILES["fileGambar"]["name"];
			$type_gambar = $_FILES["fileGambar"]["type"];
			$ext = pathinfo($name_gambar,PATHINFO_EXTENSION);
			$getname = explode('.', $name_gambar);
			
			$random = rand(1,999999);
			$nameFile = "akun_".$user."_".$random.".".$ext;
			$path = "akun/akun_".$user."_".$random.".".$ext;
			$upload = move_uploaded_file($temp_gambar, $path);
			
			if ($upload){
				$data = array(
					'userid'		=> $user,
					'password'		=> $password,
					'nama'			=> $nama,
					'email'			=> $email,
					'foto'			=> $path,
					'kelamin'		=> $kelamin,
					'mode'			=> $mode
				);
			}else{
				$this->response([
			        'status' => FALSE,
			        'message' => 'Gagal Upload File Foto'
			    ], REST_Controller::HTTP_NOT_FOUND);
			}
		}else{
			$data = array(
				'userid'		=> $user,
				'password'		=> $password,
				'nama'			=> $nama,
				'email'			=> $email,
				'kelamin'		=> $kelamin,
				'mode'			=> $mode
			);
		}
		
		$cekData = $this->api_model->getUsers($user);
		if ($cekData->num_rows() >= 1)
		{
			//update data user
			$where = array('userid'=>$user);
			$simpan = $this->api_model->updateUser($data,$where);
			$message = array('status' => TRUE,'message'=>'Berhasil Update Data User');
		}else{
			$simpan = $this->api_model->simpanUser($data);
			$message = array('status' => TRUE,'message'=>'Berhasil Tambah Data User');
		}
		
		if ($simpan){
			$this->set_response($message, REST_Controller::HTTP_OK);
		}else{
			$this->response([
		        'status' => FALSE,
		        'message' => 'Gagal Tambah Data User'
		    ], REST_Controller::HTTP_NOT_FOUND);
		}
    }
    
    public function getUser_get(){ 
		$user = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getUsers($user);
		if ($fetch_row->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			foreach($fetch_row->result() as $row){
				$no++;
				
				$data_no = array ();
				$data_no["userid"] = $row->userid;
				$data_no["mode"] = $row->mode;
				$data_no["password"] = $this->api_model->decryptPass($row->password);
				$data_no["nama"] = $row->nama;
				$data_no["email"] = $row->email;
				$data_no["foto"] = $row->foto;
				$data_no["kelamin"] = $row->kelamin;

				$dataArray["data"]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
	}
	
	public function delUsers_get()
    {
        $id = $this->uri->segment(4);
        
        
        // Validate the id.
        if ($id == "")
        {
            // Set the response and exit
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $where = array('userid'=>$id);
		$delete = $this->api_model->deleteUser($where);
		if ($delete){
			$message = [
	            'id' => $id,
	            'message' => 'Berhasil Hapus Users'
	        ];

	        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
		}else{
			$this->response([
                'status' => FALSE,
                'message' => 'Gagal hapus user'
            ], REST_Controller::HTTP_NOT_FOUND);
		}
		
    }

	public function mode_get(){ 
		$mode = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getMode($mode);
		if ($fetch_row->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			foreach($fetch_row->result() as $row){
				$no++;
				
				$data_no = array ();
				$data_no["id"] = $row->id;
				$data_no["otoritas"] = $row->auth;
				$data_no["keterangan"] = $row->keterangan;

				$dataArray["data"][]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
	}
    
    public function menu_post()
    {
		$id = $this->post('id');
		$mode = $this->post('mode');
		$menu = $this->post('menu');
		$keterangan = $this->post('keterangan');
		
		$data = array(
					'otoritas'	=> $mode,
					'menu'		=> $menu,
					'keterangan'=> $keterangan
		);

		$cekData = $this->api_model->getMenu($id);

		if ($cekData->num_rows() >= 1)
		{
			//update data user
			$where = array('id'=>$id);
			$simpan = $this->api_model->updateMenu($data,$where);
			$message = array('status' => TRUE,'message'=>'Berhasil Update Menu');
		}else{
			$data['id'] = null;
			$simpan = $this->api_model->simpanMenu($data);
			$message = array('status' => TRUE,'message'=>'Berhasil Tambah Menu');
		}
		
		if ($simpan){
			$this->set_response($message, REST_Controller::HTTP_OK);
		}else{
			$this->response([
		        'status' => FALSE,
		        'message' => 'Gagal Tambah Menu'
		    ], REST_Controller::HTTP_NOT_FOUND);
		}
    }
    
    public function menu_get()
    {
		$id = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$getData = $this->api_model->getMenu($id);
		if ($getData->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			$data_no = '';
			foreach($getData->result() as $row){
				$no++;
				
				$data_no = array ();
				
				$id = $row->id;
				$mode = $row->otoritas;
				$menu = $row->menu;
				$keterangan = $row->keterangan;
				
				$getMode = $this->api_model->getMode($mode);
				if ($getMode->num_rows() >=1){
					$nmode = $getMode->row()->auth;
				}else{
					$nmode = "Mode Kode ".$mode." Tidak Diketahui Otoritasnya";
				}
				$aksi = "
					<button id=\"btnEdit\" class=\"btn btn-primary\" onclick=\"editMenu('".$id."')\"><i class=\"fa fa-pencil\"> </i>Edit</button>
					<button id=\"btnEdit\" class=\"btn btn-danger\" onclick=\"deleteMenu('".$id."')\"><i class=\"fa fa-trash\"></i> Hapus</button>";
				
				$data_no [] = $no;
				$data_no [] = $nmode;
				$data_no [] = $menu;
				$data_no [] = $keterangan;
				$data_no [] = $aksi;

				$dataArray["data"][]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
    }

    public function getMenu_get(){ 
		$user = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getMenu($user);
		if ($fetch_row->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			foreach($fetch_row->result() as $row){
				$no++;
				
				$data_no = array ();
				$data_no["id"] = $row->id;
				$data_no["mode"] = $row->otoritas;
				$data_no["menu"] = $row->menu;
				$data_no["keterangan"] = $row->keterangan;
				
				$dataArray["data"]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
	}
	
	public function delMenu_get()
    {
        $id = $this->uri->segment(4);
        
        
        // Validate the id.
        if ($id == "")
        {
            // Set the response and exit
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $where = array('id'=>$id);
		$delete = $this->api_model->deleteMenu($where);
		if ($delete){
			$message = [
	            'id' => $id,
	            'message' => 'Berhasil Hapus Menu'
	        ];

	        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
		}else{
			$this->response([
                'status' => FALSE,
                'message' => 'Gagal hapus Menu'
            ], REST_Controller::HTTP_NOT_FOUND);
		}
		
    }

    public function getMode_get()
    {
		$id = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$getData = $this->api_model->getMode($id);
		if ($getData->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			$data_no = '';
			foreach($getData->result() as $row){
				$no++;
				
				$data_no = array ();
				
				$id = $row->id;
				$auth = $row->auth;
				$keterangan = $row->keterangan;
				
				$aksi = "
					<button id=\"btnEdit\" class=\"btn btn-primary\" onclick=\"editMode('".$id."')\"><i class=\"fa fa-pencil\"> </i>Edit</button>
					<button id=\"btnEdit\" class=\"btn btn-danger\" onclick=\"deleteMode('".$id."')\"><i class=\"fa fa-trash\"></i> Hapus</button>";
				
				$data_no [] = $no;
				$data_no [] = $auth;
				$data_no [] = $keterangan;
				$data_no [] = $aksi;

				$dataArray["data"][]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
    }

    public function otoritas_get(){ 
		$mode = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getMode($mode);
		if ($fetch_row->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			foreach($fetch_row->result() as $row){
				$no++;
				
				$data_no = array ();
				$data_no["id"] = $row->id;
				$data_no["otoritas"] = $row->auth;
				$data_no["keterangan"] = $row->keterangan;

				$dataArray["data"]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
	}
    
    public function otoritas_post()
    {
		$id = $this->post('id');
		$auth = $this->post('otoritas');
		$keterangan = $this->post('keterangan');
		
		$data = array(
					'auth'	=> $auth,
					'keterangan'=> $keterangan
		);

		$cekData = $this->api_model->getMode($id);

		if ($cekData->num_rows() >= 1)
		{
			//update data user
			$where = array('id'=>$id);
			$simpan = $this->api_model->updateMode($data,$where);
			$message = array('status' => TRUE,'message'=>'Berhasil Update Mode');
		}else{
			$data['id'] = null;
			$simpan = $this->api_model->simpanMode($data);
			$message = array('status' => TRUE,'message'=>'Berhasil Tambah Mode');
		}
		
		if ($simpan){
			$this->set_response($message, REST_Controller::HTTP_OK);
		}else{
			$this->response([
		        'status' => FALSE,
		        'message' => 'Gagal Tambah Mode'
		    ], REST_Controller::HTTP_NOT_FOUND);
		}
    }

    public function delMode_get()
    {
        $id = $this->uri->segment(4);
        
        
        // Validate the id.
        if ($id == "")
        {
            // Set the response and exit
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $where = array('id'=>$id);
		$delete = $this->api_model->deleteMode($where);
		if ($delete){
			$message = [
	            'id' => $id,
	            'message' => 'Berhasil Hapus Mode'
	        ];

	        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
		}else{
			$this->response([
                'status' => FALSE,
                'message' => 'Gagal hapus Mode'
            ], REST_Controller::HTTP_NOT_FOUND);
		}
		
    }

    //Pengelolaan Aplikasi Rekon
    public function listFileGlobal_get()
    {
		$server = $this->_getServer();
		$id = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$getData = $this->api_model->getFileGlobal($id);
		if ($getData->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			$data_no = '';
			foreach($getData->result() as $row){
				$no++;
				
				$data_no = array ();
				
				$id = $row->id;
				$file = $server.$row->namaFile;
				$namaFile = "<a href=\"".$file."\" target=\"_blank\">".$row->namaFile."</a>";
				$keteranganFile = $row->keteranganFile;
				$readFile = $row->readFile;
				$bank = $row->bank;
				$gd = $row->gd;
				
				$statusFile = ($readFile == '1')?"<font style=\"background-color:green;color:white;\">Telat dicatat</font>":"<button id=\"btnSinkron_".$id."\" class=\"btn btn-success\" onclick=\"bookFile('".$id."')\"><i class=\"fa fa-chart\"> </i>POSTING</button> ";
				$glodil = ($gd=='G')?"Global":"Detil";

				if ($readFile == '1'){
					$aksi = "<font style=\"background-color:red;color:white;\">Tidak Bisa Edit/Hapus</font>";
				}else{
					$aksi = "
					<button id=\"btnHapus_".$id."\" class=\"btn btn-danger\" onclick=\"deleteFileGlobal('".$id."')\"><i class=\"fa fa-trash\"></i> Hapus</button>";
				}
				
				
				$data_no [] = $no;
				$data_no [] = $namaFile;
				$data_no [] = $keteranganFile;
				$data_no [] = $statusFile;
				$data_no [] = $bank;
				$data_no [] = $glodil;
				$data_no [] = $aksi;

				$dataArray["data"][]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
    }

    public function fileGlobal_post()
    {
		$id = $this->post('id');
		$keterangan = $this->post('keterangan');
		$bank = $this->post('bank');
		
		if (isset($_FILES["fileUpload"]["tmp_name"])){
			$temp_gambar = $_FILES["fileUpload"]["tmp_name"];
			$name_gambar = $_FILES["fileUpload"]["name"];
			$type_gambar = $_FILES["fileUpload"]["type"];
			$ext = pathinfo($name_gambar,PATHINFO_EXTENSION);
			$getname = explode('.', $name_gambar);
			
			$random = rand(1,999999);
			$nameFile = "fileGlobal_".$getname[0]."_".$random.".".$ext;
			$path = "fileUpload/fileGlobal_".$getname[0]."_".$random.".".$ext;
			$upload = move_uploaded_file($temp_gambar, $path);
			
			if ($upload){
				$data = array(
					'id'			=> $id,
					'keteranganFile'=> $keterangan,
					'namaFile'		=> $path,
					'bank'			=> $bank,
					'gd'			=> 'G',
					'readFile'		=> '0'
				);
			}else{
				$this->response([
			        'status' => FALSE,
			        'message' => 'Gagal Upload File Foto'
			    ], REST_Controller::HTTP_NOT_FOUND);
			}
		
			$cekData = $this->api_model->getFileGlobal($id);
			if ($cekData->num_rows() >= 1)
			{
				//update data user
				$where = array('userid'=>$user);
				$simpan = $this->api_model->updateFileGlobal($data,$where);
				$message = array('status' => TRUE,'message'=>'Berhasil Upload File Global');
			}else{
				$simpan = $this->api_model->simpanFileGlobal($data);
				$message = array('status' => TRUE,'message'=>'Berhasil Upload File Global');
			}
			
			if ($simpan){
				$this->set_response($message, REST_Controller::HTTP_OK);
			}else{
				$this->response([
			        'status' => FALSE,
			        'message' => 'Gagal Upload File Global'
			    ], REST_Controller::HTTP_NOT_FOUND);
			}

		}else{
			$this->response([
			        'status' => FALSE,
			        'message' => 'File Upload tidak ada'
			], REST_Controller::HTTP_NOT_FOUND);
		}
    }

    public function fileGlobal_get(){ 
		$mode = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getFileGlobal($mode);
		if ($fetch_row->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			foreach($fetch_row->result() as $row){
				$no++;
				
				$data_no = array ();
				$data_no["id"] = $row->id;
				$data_no["nama_file"] = $row->namaFile;
				$data_no["keterangan_file"] = $row->keteranganFile;
				$data_no["read_file"] = $row->readFile;
				$data_no["bank"] = $row->bank;
				$data_no["gd"] = $row->gd;

				$dataArray["data"]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
	}

	public function bookFileGlobal_get(){ 
		ini_set("upload_max_filesize",25);
		ini_set('max_execution_time', 400);
		
		$id = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getFileGlobal($id);
		if ($fetch_row->num_rows() >= 1){
			$row = $fetch_row->row();
			$file = $row->namaFile;
			$bank = $row->bank;
			
			try {
				$inputFileType = PHPExcel_IOFactory::identify($file);
				$objReader = PHPExcel_IOFactory::createReader($inputFileType);
				$objPHPExcel = $objReader->load($file);
			} catch(Exception $e) {
				die('Error loading file "'.pathinfo($file,PATHINFO_BASENAME).'": '.$e->getMessage());
			}

			$sheet = $objPHPExcel->getSheet(0);
			$highestRow = $sheet->getHighestRow();
			$highestColumn = $sheet->getHighestColumn();
			$contentArr = "Post Date;Value Date;Branch;Journal No;Description;Debet;Credit<br/>";
			for ($row = 2; $row <= $highestRow; $row++){  
				$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
				NULL,
				TRUE,
				FALSE);
				
				if ($bank == 'bni'){
					$debit = str_replace(',', '', $rowData[0][5]);
					$credit = str_replace(',', '', $rowData[0][6]);
					
					$x1 = explode(' ',$rowData[0][0]);
					$d11 = explode('/', $x1[0]);
					$d12 = explode('.', $x1[1]);
					$dmy1 = '20'.$d11[2]."-".$d11[1]."-".$d11[0];
					$his1 = $d12[0].":".$d12[1].":".$d12[2];
					$pdx = $dmy1." ".$his1;
					
					$x2 = explode(' ',$rowData[0][1]);
					$d21 = explode('/', $x2[0]);
					$d22 = explode('.', $x2[1]);
					$dmy2 = '20'.$d21[2]."-".$d21[1]."-".$d21[0];
					$his2 = $d22[0].":".$d22[1].":".$d22[2];
					$vdx = $dmy2." ".$his2;

					$data = array(
						'post_date' => $pdx,
						'value_date' => $vdx,
						'branch' => $rowData[0][2],
						'journal_no' => $rowData[0][3],
						'description' => $rowData[0][4],
						'debit' => $debit,
						'credit' => $credit,
						'bank' => $bank
					);
					$insert = $this->api_model->insertBook($data);
				}elseif ($bank == 'bukopin'){
					$debit = str_replace(',', '', $rowData[0][3]);
					$credit = str_replace(',', '', $rowData[0][4]);
					$saldo = str_replace(',', '', $rowData[0][5]);
					/*
					$x = explode('/',$rowData[0][0]);
					$d = $x[0];
					$m = $x[1];
					$y = $x[2];
					*/
					$date = date("Y-m-d H:i:s", $this->api_model->ExcelToPHP($rowData[0][0]));
					
					$data = array(
						'post_date' => $date,
						'value_date' => $date,
						'branch' => $rowData[0][2],
						'journal_no' => $rowData[0][2],
						'description' => $rowData[0][1],
						'debit' => $debit,
						'credit' => $credit,
						'saldo' => $saldo,
						'bank' => $bank
					);
					$insert = $this->api_model->insertBook($data);
				}else{}
				
			} 
			$this->api_model->ubahFileGlobal($id);
			$this->set_response(array('status'=>TRUE), REST_Controller::HTTP_OK);
		}else{
			$this->response([
			        'status' => FALSE,
			        'message' => 'Gagal Upload File Global'
			], REST_Controller::HTTP_NOT_FOUND);
		}
	}

	public function delFileGlobal_get()
    {
        $id = $this->uri->segment(4);
        
        // Validate the id.
        if ($id == "")
        {
            // Set the response and exit
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }
        
        $row = $this->api_model->getFileGlobal($id);
        $namaFile = $row->row()->namaFile;

        $where = array('id'=>$id);
		$delete = $this->api_model->deleteFileGlobal($where);
		if ($delete){
			$message = [
	            'id' => $id,
	            'message' => 'Berhasil Hapus File'
	        ];

	        //file
	        $file = $namaFile;
			if (is_readable($file) && unlink($file)) {
			    echo "The file has been deleted";
			} else {
			    echo "The file was not found or not readable and could not be deleted";
			}
			//--------------

	        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
		}else{
			$this->response([
                'status' => FALSE,
                'message' => 'Gagal hapus Mode'
            ], REST_Controller::HTTP_NOT_FOUND);
		}
		
    }

    //File Detil
    public function listFileDetil_get()
    {
		$id = $this->uri->segment(4);
		$dataArray = array();
		$server = $this->_getServer();
		//ambil data di model
		$getData = $this->api_model->getFileDetil($id);
		if ($getData->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			$data_no = '';
			foreach($getData->result() as $row){
				$no++;
				
				$data_no = array ();
				
				$id = $row->id;
				$file = $server.$row->namaFile;
				$namaFile = "<a href=\"".$file."\" target=\"_blank\">".$row->namaFile."</a>";
				$keteranganFile = $row->keteranganFile;
				$readFile = $row->readFile;
				$book = $row->id_book;
				$bank = $row->bank;
				$gd = $row->gd;
				
				if ($book != '0'){
					$getbook = $this->api_model->getIdbook($book)->row();
					$des_book = $getbook->Description;
				}else{
					$des_book = "";
				}

				$statusFile = ($readFile == '1')?"<font style=\"background-color:green;color:white;\">Telah dicatat</font>":"<button id=\"btnSinkron_".$id."\" class=\"btn btn-warning\" onclick=\"sinkronFile('".$id."')\"><i class=\"fa fa-chart\"> </i>Sinkronisasi</button> ";
				$glodil = ($gd=='G')?"Global":"Detil";

				if ($readFile == '1'){
					$aksi = "<font style=\"background-color:red;color:white;\">Tidak Bisa Edit/Hapus</font>";
				}else{
					$aksi = "
					<button id=\"btnHapus_".$id."\" class=\"btn btn-danger\" onclick=\"deleteFileDetil('".$id."')\"><i class=\"fa fa-trash\"></i> Hapus</button>";
				}
				
				
				$data_no [] = $no;
				$data_no [] = $namaFile;
				$data_no [] = $keteranganFile;
				$data_no [] = $des_book;
				$data_no [] = $statusFile;
				$data_no [] = $bank;
				$data_no [] = $glodil;
				$data_no [] = $aksi;

				$dataArray["data"][]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
    }

    public function fileDetil_post()
    {
		$id = $this->post('id');
		$keterangan = $this->post('keterangan');
		$bank = $this->post('bank');
		$id_book = $this->post('id_book');
		
		if (isset($_FILES["fileUpload"]["tmp_name"])){
			$temp_gambar = $_FILES["fileUpload"]["tmp_name"];
			$name_gambar = $_FILES["fileUpload"]["name"];
			$type_gambar = $_FILES["fileUpload"]["type"];
			$ext = pathinfo($name_gambar,PATHINFO_EXTENSION);
			$getname = explode('.', $name_gambar);
			
			$random = rand(1,999999);
			$nameFile = "fileDetil_".$getname[0]."_".$random.".".$ext;
			$path = "fileUpload/fileDetil_".$getname[0]."_".$random.".".$ext;
			$upload = move_uploaded_file($temp_gambar, $path);
			
			if ($upload){
				$data = array(
					'id'			=> $id,
					'keteranganFile'=> $keterangan,
					'namaFile'		=> $path,
					'bank'			=> $bank,
					'id_book'		=> $id_book,
					'gd'			=> 'D',
					'readFile'		=> '0'
				);
			}else{
				$this->response([
			        'status' => FALSE,
			        'message' => 'Gagal Upload File Bank'
			    ], REST_Controller::HTTP_NOT_FOUND);
			}
		
			$cekData = $this->api_model->getFileGlobal($id);
			if ($cekData->num_rows() >= 1)
			{
				//update data user
				$where = array('userid'=>$user);
				$simpan = $this->api_model->updateFileGlobal($data,$where);
				$message = array('status' => TRUE,'message'=>'Berhasil Upload File Global');
			}else{
				$simpan = $this->api_model->simpanFileGlobal($data);
				$message = array('status' => TRUE,'message'=>'Berhasil Upload File Global');
			}
			
			if ($simpan){
				$this->set_response($message, REST_Controller::HTTP_OK);
			}else{
				$this->response([
			        'status' => FALSE,
			        'message' => 'Gagal Upload File Global'
			    ], REST_Controller::HTTP_NOT_FOUND);
			}

		}else{
			$this->response([
			        'status' => FALSE,
			        'message' => 'File Upload tidak ada'
			], REST_Controller::HTTP_NOT_FOUND);
		}
    }

    public function fileDetil_get(){ 
		$mode = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getFileDetil($mode);
		if ($fetch_row->num_rows() >= 1){
			$dataArray["status"] = true;
			$dataArray["data"] = array();
			//$data_surat["msg"] = "Data Berikut";
			$no=0;
			foreach($fetch_row->result() as $row){
				$no++;
				
				$data_no = array ();
				$data_no["id"] = $row->id;
				$data_no["nama_file"] = $row->namaFile;
				$data_no["keterangan_file"] = $row->keteranganFile;
				$data_no["read_file"] = $row->readFile;
				$data_no["bank"] = $row->bank;
				$data_no["gd"] = $row->gd;

				$dataArray["data"]= $data_no;
			}
			
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
	}

	public function ubahNonUkt_get(){ 
		$id = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getIdDetbook($id);
		if ($fetch_row->num_rows() >= 1){
			$rec = $fetch_row->row();
			$non_ukt = $rec->non_ukt;

			//update menjadi 1/0
			if ($non_ukt == '0'){
				$data = array('non_ukt' => '1');
			}else{
				$data = array('non_ukt' => '0');
			}

			$update = $this->api_model->upNonUkt($data,$id);
			if ($update){
				$dataArray["status"] = true;
				$dataArray["msg"] = "Berhasil Update";
			}else{
				$dataArray["status"] = false;
				$dataArray["msg"] = "Gagal Update";
			}
		}else{
			$dataArray["status"] = false;
			$dataArray["msg"] = "Tidak Ada Data";
		}
		header('Content-type: application/json');
		echo json_encode($dataArray);
	}
	public function delFileDetil_get()
    {
        $id = $this->uri->segment(4);
        
        
        // Validate the id.
        if ($id == "")
        {
            // Set the response and exit
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }
        
        $row = $this->api_model->getFileDetil($id);
        $namaFile = $row->row()->namaFile;

        $where = array('id'=>$id);
		$delete = $this->api_model->deleteFileGlobal($where);
		if ($delete){
			$message = [
	            'id' => $id,
	            'message' => 'Berhasil Hapus File'
	        ];
	        
	        //file
	        $file = $namaFile;
			if (is_readable($file) && unlink($file)) {
			    echo "The file has been deleted";
			} else {
			    echo "The file was not found or not readable and could not be deleted";
			}
			//--------------

	        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
		}else{
			$this->response([
                'status' => FALSE,
                'message' => 'Gagal hapus Mode'
            ], REST_Controller::HTTP_NOT_FOUND);
		}
		
    }
    

    public function recFileGlobal_get()
    {
    	$id = $this->uri->segment(4);
    	$dataArray = array();
			
		if ($id == 'bni' || $id == 'bukopin'){
    		//ambil data di model
			$fetch_row = $this->api_model->getbookGlobal($id);
			if ($fetch_row->num_rows() >= 1){
				$dataArray["status"] = true;
				$dataArray["data"] = array();
				//$data_surat["msg"] = "Data Berikut";
				$no=0;
				foreach($fetch_row->result() as $row){
					$no++;
					
					$data_no = array ();
					$data_no["id"] = $row->id;
					$data_no["post_date"] = $row->post_date;
					$data_no["description"] = $row->Description;
					$data_no["journal_no"] = $row->journal_no;

					$dataArray["data"][]= $data_no;
				}
			}else{
				$dataArray["status"] = false;
				$dataArray["msg"] = "Tidak Ada Data";
			}
    	}else{
    		$dataArray["status"] = true;
			$dataArray["data"] = array();
			$data_no = array ();
			$data_no["id"] = 0;
			$dataArray["data"][]= $data_no;
    	}
    	header('Content-type: application/json');
		echo json_encode($dataArray);
    }

    public function bookFileDetil_get(){ 
		ini_set("upload_max_filesize",25);
		ini_set('max_execution_time', 3600);
		
		$id = $this->uri->segment(4);
		$dataArray = array();
		//ambil data di model
		$fetch_row = $this->api_model->getFileDetil($id);
		if ($fetch_row->num_rows() >= 1)
		{

			$row = $fetch_row->row();
			$file = $row->namaFile;
			$bank = $row->bank;
			$id_book = $row->id_book;

			//Bank Bukopin
			if ($bank == 'bkp')
			{
				$getIdBook = $this->api_model->getIdbook($id_book);
				if ($getIdBook->num_rows() <= 0){
					$this->response([
					        'status' => FALSE,
					        'message' => 'Gagal Upload File Global'
					], REST_Controller::HTTP_NOT_FOUND);
				}else{
					$recIdbook = $getIdBook->row();
					$post_date = $recIdbook->post_date;
					$value_date = $recIdbook->value_date;
					$branch = $recIdbook->branch;
					$journal_no = $recIdbook->journal_no;
					$description = $recIdbook->Description;
					
					try {
						$inputFileType = PHPExcel_IOFactory::identify($file);
						$objReader = PHPExcel_IOFactory::createReader($inputFileType);
						$objPHPExcel = $objReader->load($file);
					} catch(Exception $e) {
						die('Error loading file "'.pathinfo($file,PATHINFO_BASENAME).'": '.$e->getMessage());
					}

					$sheet = $objPHPExcel->getSheet(0);
					$highestRow = $sheet->getHighestRow();
					$highestColumn = $sheet->getHighestColumn();
					$contentArr = "Post Date;Value Date;Branch;Journal No;Description;Debet;Credit<br/>";
					
					for ($row = 3; $row <= $highestRow; $row++){  
						$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
						NULL,
						TRUE,
						FALSE);
						
						$no = $rowData[0][0];
						$tgl = $rowData[0][1];
						$nim = $rowData[0][2];
						$nama = $rowData[0][3];
						$nominal = str_replace(',', '', $rowData[0][4]);
						$debit = 0;
						$credit = $nominal;
						if (trim($nim) == '' && trim($nama) == '' && trim($nominal) == '' ){
							break;
						}

						$pdx = $post_date;
						$vdx = $value_date;
						$dpx = $tgl;
						//echo $rowData[0][0]."#".$rowData[0][1]."#".$rowData[0][2]."#".$rowData[0][3]."#".$rowData[0][4]."<br/>";

						$data = array(
							'bank' => $bank,
							'id_book' => $id_book,
							'post_date' => $pdx,
							'value_date' => $vdx,
							'description' => $description,
							'nim' => $rowData[0][2],
							'nama' => $rowData[0][3],
							'debit' => $debit,
							'credit' => $credit,
							'branch' => $branch,
							'journal_no' => $journal_no,
							'date_pay' => $rowData[0][1]
						);
						$insert = $this->api_model->insertBookDetil($data);
					}
				}
			//Bank Mandiri
			}elseif ($bank == 'mdr')
			{
				try {
					$inputFileType = PHPExcel_IOFactory::identify($file);
					$objReader = PHPExcel_IOFactory::createReader($inputFileType);
					$objPHPExcel = $objReader->load($file);
				} catch(Exception $e) {
					die('Error loading file "'.pathinfo($file,PATHINFO_BASENAME).'": '.$e->getMessage());
				}

				$sheet = $objPHPExcel->getSheet(0);
				$highestRow = $sheet->getHighestRow();
				$highestColumn = $sheet->getHighestColumn();
				$contentArr = "Post Date;Value Date;Branch;Journal No;Description;Debet;Credit<br/>";
				
				for ($row = 2; $row <= $highestRow; $row++)
				{
					$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
					NULL,
					TRUE,
					FALSE);
					/*
					$dmy0 = explode("/",$rowData[0][1]);
					$dd0 = "20".$dmy0[2]."-".$dmy0[1]."-".$dmy0[0];
					$dmy1 = explode("/",$rowData[0][2]);
					$dd1 = "20".$dmy1[2]."-".$dmy1[1]."-".$dmy1[0];
					*/
					$acc_no = $rowData[0][0];
					$post_date = $rowData[0][1];
					$value_date = $rowData[0][2];
					$transaksi_code = $rowData[0][3];
					$description1 = $rowData[0][4];
					$description2 = $rowData[0][5];
					$ref_no = $rowData[0][6];
					$debit = str_replace(",", "", $rowData[0][7]);
					$credit = str_replace(",", "", $rowData[0][8]);
					$description = trim($description2);
					//$lenDes = strlen(description);
					$nim = substr($description2,20,20);
					
					//$description = trim($description1)."".trim($description2);

					/*
					if (!isset($transaksi_code)){
						$this->response([
						        'status' => FALSE,
						        'message' => 'Format tidak sesuai'
						], REST_Controller::HTTP_NOT_FOUND);
						exit;
					}
					
	
					$x = explode('FFFFFF', $description2);
					if (!isset($x[1]))
					{
						continue;
					}
					
					$nim = trim($x[1]);
					*/	
					/*
					//header('Access-Control-Allow_Origin: *');
					$url = "http://192.168.9.45/siakad_api/api/as400/dataMahasiswa/".$nim;
					$page = fopen($url, "r");
					while (!feof($page))
					//print fgets($page,1024);
					$response = fgets($page,1024);
					$json = str_replace(array("\t","\n"), "", $response);
					$getdata = json_decode($json);
						
					//$no = $_POST['start'];
					$status = isset($getdata->status)?$getdata->status:false;
					if ($status == false){
						$nama = 'Tidak diketahui';
					}else{
						$kontenData = $getdata->isi;
						$rec = count($kontenData);
						foreach ($kontenData as $item) {
							$nama = $item->nama;
						}
					}
					fclose($page);
					*/
					$nama = '';
					$dpx = $post_date;
					$x = explode('/', $post_date);
					$d = $x[0];
					$m = $x[1];
					$y = $x[2];
					$pdx = "20".$y."-".$m."-".$d." 00:00:00";
					$vdx = "20".$y."-".$m."-".$d." 00:00:00";
					$datePay = "20".$y."-".$m."-".$d;
					$data= array(
							'bank' => $bank,
							'id_book' => $id_book,
							'post_date' => $pdx,
							'value_date' => $vdx,
							'description' => $description,
							'nim' => $nim,
							'nama' => $nama,
							'debit' => $debit,
							'credit' => $credit,
							'branch' => $transaksi_code,
							'journal_no' => $transaksi_code,
							'date_pay' => $datePay
						);
					//print_r($data);
					$insert = $this->api_model->insertBookDetil($data);
					
				} 
			//Bank BTN
			}elseif ($bank == 'btn')
			{
				try {
					$inputFileType = PHPExcel_IOFactory::identify($file);
					$objReader = PHPExcel_IOFactory::createReader($inputFileType);
					$objPHPExcel = $objReader->load($file);
				} catch(Exception $e) {
					die('Error loading file "'.pathinfo($file,PATHINFO_BASENAME).'": '.$e->getMessage());
				}

				$sheet = $objPHPExcel->getSheet(0);
				$highestRow = $sheet->getHighestRow();
				$highestColumn = $sheet->getHighestColumn();
				$contentArr = "Post Date;Value Date;Branch;Journal No;Description;Debet;Credit<br/>";
				
				for ($row = 5; $row <= $highestRow; $row++){  
					$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
					NULL,
					TRUE,
					FALSE);
					
					$acc_no = $rowData[0][0];
					$post_date = $rowData[0][1];
					$post_time = $rowData[0][2];
					$value_date = $rowData[0][3];
					$value_time = $rowData[0][4];
					$description = $rowData[0][5];
					$debit = $rowData[0][6];
					$credit = $rowData[0][7];
					$branch = $rowData[0][8];
					$journal_no = $rowData[0][9];
					
					/*
					if (!isset($post_time)){
						$this->response([
						        'status' => FALSE,
						        'message' => 'Format tidak sesuai'
						], REST_Controller::HTTP_NOT_FOUND);
						exit;
					}else{}
					*/

					$x = explode(' ', $description);
					if (!isset($x[2])){
						continue;
					}

					$nim = trim($x[2]);
					
					/*
					//header('Access-Control-Allow_Origin: *');
					$url = "http://192.168.9.45/siakad_api/api/as400/dataMahasiswa/".$nim;
					$page = fopen($url, "r");
					while (!feof($page))
					//print fgets($page,1024);
					$response = fgets($page,1024);
					$json = str_replace(array("\t","\n"), "", $response);
					$getdata = json_decode($json);
					
					//$no = $_POST['start'];
					$status = isset($getdata->status)?$getdata->status:false;
					if ($status == false){
						//echo "NIM : ".$nim." Nama : Undefined <br/>";
						$nama = 'Tidak diketahui';
					}else{
						$kontenData = $getdata->isi;
						$rec = count($kontenData);
						foreach ($kontenData as $item) {
							$nama = $item->nama;
						}
					}
					fclose($page);
					*/
					$nama = '';

					$k = explode('/', $post_date);
					$ddP = $k[0];
					$mmP = $k[1];
					$yyP = $k[2];
					$post = $yyP."-".$mmP."-".$ddP;
					$l = explode('/', $value_date);
					$ddV = $l[0];
					$mmV = $l[1];
					$yyV = $l[2];
					$value = $yyV."-".$mmV."-".$ddV;

					$dpx = $post;
					$pdx = $post." ".$post_time;
					$vdx = $value." ".$value_time;

					//echo $bank."#".$id_book."#".$pdx."#".$vdx."#".$description."#".$nim."#".$nama."#".$debit."#".$credit."#".$branch."#".$journal_no."#".$dpx."<br/>";

					$data= array(
						'bank' => $bank,
						'id_book' => $id_book,
						'post_date' => $pdx,
						'value_date' => $vdx,
						'description' => $description,
						'nim' => $nim,
						'nama' => $nama,
						'debit' => $debit,
						'credit' => $credit,
						'branch' => $branch,
						'journal_no' => $journal_no,
						'date_pay' => $dpx,
					);
					$insert = $this->api_model->insertBookDetil($data);
					
				} 
			//Bank BNI	
			}elseif ($bank == 'bni')
			{
				$getIdBook = $this->api_model->getIdbook($id_book);
				if ($getIdBook->num_rows() <= 0){
					$this->response([
					        'status' => FALSE,
					        'message' => 'Gagal Upload File Global'
					], REST_Controller::HTTP_NOT_FOUND);
				}else{}
				
				$recIdbook = $getIdBook->row();
				$post_date = $recIdbook->post_date;
				$value_date = $recIdbook->value_date;
				$branch = $recIdbook->branch;
				$journal_no = $recIdbook->journal_no;
				$description = $recIdbook->Description;

				$file_lines = file($file);
				$arr = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    			$c = ( false === $arr) ? 0 : count($arr);
    			
				foreach ($file_lines as $line) {
				    $data = explode(';',$line);
				    if ($data[0] == '1'){
				    	continue;
				    }elseif ($data[0] == '3'){
				    	break;
				    }else{
				    	//echo $line."<br/>";
				    	$m = $data[0];
						$no = $data[1];
						$nim = $data[2];
						$nama = $data[3];
						$nominal = intval($data[4]);
						$tgl = $data[6];

						$debit = 0;
						$credit = $nominal;	
						$dmy = explode('/', $tgl);
						$date_pay = "20".$dmy[2]."-".$dmy[0]."-".$dmy[1];
						$pdx = $post_date;
						$vdx = $value_date;
						
				    	$data = array (
							'bank' => $bank,
							'id_book' => $id_book,
							'post_date' => $pdx,
							'value_date' => $vdx,
							'description' => $description,
							'nim' => $nim,
							'nama' => $nama,
							'debit' => $debit,
							'credit' => $credit,
							'branch' => $branch,
							'journal_no' => $journal_no,
							'date_pay' => $date_pay
						);
						$insert = $this->api_model->insertBookDetil($data);
				    }
				}
			}else{}
			
			$this->api_model->ubahFileGlobal($id);
			$this->set_response(array('status'=>TRUE), REST_Controller::HTTP_OK);

		}else{
			$this->response([
			        'status' => FALSE,
			        'message' => 'Gagal Upload File Global'
			], REST_Controller::HTTP_NOT_FOUND);
		}

	}

	public function tarikTIK_post()
	{
		ini_set("upload_max_filesize",25);
		ini_set('max_execution_time', 360);

		$bank = $this->input->post('bank');
		$tglAwal = $this->input->post('tglAwal');
		$tglAkhir = $this->input->post('tglAkhir');
		$dataArray = array();
		
		if ($bank=='' || $tglAwal == '' || $tglAkhir == ''){
			$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tidak ada proses'
			], REST_Controller::HTTP_NOT_FOUND);
		}else if ($tglAwal > $tglAkhir){
			$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tanggal Awal Lebih dari tanggal Akhir'
			], REST_Controller::HTTP_NOT_FOUND);
		}else{
			
			$tgl0 = explode('/', $tglAwal);
			$tgl1 = explode('/', $tglAkhir);
			$tgl_awal = $tgl0[2]."-".$tgl0[0]."-".$tgl0[1];
			$tgl_akhir = $tgl1[2]."-".$tgl1[0]."-".$tgl1[1];
			$dataArray ['status'] = true;
			$dataArray ['data'] = array();

			if ($bank == 'bni'){

				$url = "http://192.168.9.45/siakad_api/api/as400/pembayaranBNI/".$tgl_awal."/".$tgl_akhir;

				$page = fopen($url, "r");
				while (!feof($page))
					//print fgets($page,1024);
				$response = fgets($page);
				$json = str_replace(array("\t","\n"), "", $response);
				$getdata = json_decode($json);
				$status = isset($getdata->status)?$getdata->status:false;
				//echo $status;
				if ($status == false){
					fclose($page);
					$this->response([
					        'status' => FALSE,
					        'pesan' => 'Tidak ditemukan data'
					], REST_Controller::HTTP_NOT_FOUND);
					exit;		
				}
				$kontenData = isset($getdata->msg)?$getdata->msg:$getdata->isi;
				$rec = count($kontenData);
				$no=0;
				foreach ($kontenData as $item) {
					$no++;
					$no_pembayaran = $item->nomor_pembayaran;
					$jns = explode('_',$item->id_record_tagihan);
					if (!isset($jns[1])){
						$jenisTagihan = 'Unknown';
					}else{
						if ($jns[0] == 'MDR'){
							$jenisTagihan = 'Penmaba Mandiri';
						}else{
							$jenisTagihan = 'UKT';
						}
					}

					$insertData = array(
						'idTagihan'			=> $item->id_record_tagihan,
						'nomorPembayaran'	=> $item->nomor_pembayaran,
						'nama'				=> $item->nama,
						'kodefakultas'		=> $item->kode_fakultas,
						'kodeProdi'			=> $item->kode_prodi,
						'kodePeriode'		=> $item->kode_periode,
						'angkatan'			=> $item->angkatan,
						'nominalTagihan'	=> $item->total_nilai_tagihan,
						'jenisTagihan'		=> $jenisTagihan,
						'tanggalPembayaran'	=> $item->waktu_transaksi,
						'kodeUnikPembayaran'=> $item->kode_unik_transaksi_bank,
						'kodeBank'			=> $item->kode_bank,
						'kanalBank'			=> $item->kanal_bayar_bank,
						'totalPembayaran'	=> $item->total_nilai_pembayaran,
						'statusPembayaran'	=> $item->status_pembayaran
					);
					
					

					$cekData = $this->api_model->getBookTIK($item->id_record_tagihan);
					if ($cekData->num_rows() >= 1){
						$row = $cekData->row();
						$where = array('id'=>$row->id);
						$simpan = $this->api_model->updateBookTIK($where,$insertData);
					}else{
						$simpan = $this->api_model->insertBookTIK($insertData);
					}
					if ($simpan){
						$data_no = array();
						$data_no ['no'] = $no;
						$data_no ['nim'] = $item->nomor_pembayaran;
						$data_no ['nama'] = $item->nama;
						$data_no ['kodeProdi'] = $item->kode_prodi;
						$data_no ['kodeFak'] = $item->kode_fakultas;
						$data_no ['tagihan'] = $item->total_nilai_tagihan;
						$data_no ['periode'] = $item->kode_periode;
						$data_no ['bank'] = $item->kode_bank;
						$data_no ['waktuTransaksi'] = $item->waktu_transaksi;
						$data_no ['nominalBayar'] = $item->total_nilai_pembayaran;
						$data_no ['status'] = ($item->status_pembayaran == '1')?'Sudah Lunas':'Belum/Dibatalkan';
						$dataArray ['data'][] = $data_no;
					}
				}
				
				fclose($page);
				$dataArray ['pesan'] = 'Berhasil BNI';

			}elseif ($bank == 'mdr'){

				$url = "http://192.168.9.45/siakad_api/api/as400/pembayaranMDR/".$tgl_awal."/".$tgl_akhir;
				$data4url = '';

			    $result = $this->api_model->callAPI('GET', $url,$data4url);
			    $getdata = json_decode($result);
			    $status = isset($getdata->status)?$getdata->status:false;
			    if ($status == false){
					$this->response([
					        'status' => FALSE,
					        'pesan' => 'Tidak ditemukan data'
					], REST_Controller::HTTP_NOT_FOUND);
					exit;		
				}
				$kontenData = isset($getdata->msg)?$getdata->msg:$getdata->isi;
				$kontenData = $getdata->isi;

				$no=0;
				foreach ($kontenData as $item) {
					$no++;
					$no_pembayaran = $item->tagihanNomor;
					
					$jns = explode('_',$item->tagihanNomor);
					if (isset($jns[1])){
						$jenisTagihan = 'UKT';
					}else{
						$jenisTagihan = $item->tagihanJnsPembayaran;
					}

					$insertData = array(
						'idTagihan'			=> $item->tagihanNomor,
						'nomorPembayaran'	=> $item->tagihanMahasiswaId,
						'nama'				=> $item->tagihanMahasiswaNama,
						'kodefakultas'		=> $item->tagihanKodeFak,
						'kodeProdi'			=> $item->tagihanKodeProdi,
						'kodePeriode'		=> $item->tagihanPeriode,
						'angkatan'			=> $item->tagihanAngkatan,
						'nominalTagihan'	=> $item->tagihanTotal,
						'jenisTagihan'		=> $jenisTagihan,
						'tanggalPembayaran'	=> $item->pembayaranTanggalBayar,
						'kodeUnikPembayaran'=> $item->pembayaranNoTransaksi,
						'kodeBank'			=> $item->tagihanKodeBank,
						'kanalBank'			=> $item->pembayaranKanal,
						'totalPembayaran'	=> $item->pembayaranNominal,
						'statusPembayaran'	=> '1'
					);
					
					$cekData = $this->api_model->getBookTIK($item->tagihanNomor);
					if ($cekData->num_rows() >= 1){
						$row = $cekData->row();
						$where = array('id'=>$row->id);
						$simpan = $this->api_model->updateBookTIK($where,$insertData);
					}else{
						$simpan = $this->api_model->insertBookTIK($insertData);
					}

					if ($simpan){

						$data_no = array();

						$data_no ['no'] = $no;
						$data_no ['nim'] = $item->tagihanMahasiswaId;
						$data_no ['nama'] = $item->tagihanMahasiswaNama;
						$data_no ['kodeProdi'] = $item->tagihanKodeProdi;
						$data_no ['kodeFak'] = $item->tagihanKodeFak;
						$data_no ['tagihan'] = $item->tagihanTotal;
						$data_no ['periode'] = $item->tagihanPeriode;
						$data_no ['bank'] = $item->tagihanKodeBank;
						$data_no ['waktuTransaksi'] = $item->pembayaranTanggalBayar;
						$data_no ['nominalBayar'] = $item->pembayaranNominal;
						$data_no ['status'] = 'Sudah Lunas';
						$dataArray ['data'][] = $data_no;
					}
				}
				
				//fclose($page);
				$dataArray ['pesan'] = 'Berhasil Bank Mandiri';

			}elseif ($bank == 'bkp'){

				$url = "http://192.168.9.45/siakad_api/api/as400/pembayaranBKP/".$tgl_awal."/".$tgl_akhir;

				$page = fopen($url, "r");
				while (!feof($page))
					//print fgets($page);
				$response = fgets($page);
				$json = str_replace(array("\t","\n"), "", $response);
				$getdata = json_decode($json);
				$status = isset($getdata->status)?$getdata->status:false;
				//$status = isset($getdata->status)?$getdata->status:false;
				if ($status == false){
					fclose($page);
					$this->response([
					        'status' => FALSE,
					        'pesan' => 'Tidak ditemukan data'
					], REST_Controller::HTTP_NOT_FOUND);
					exit;		
				}
				$kontenData = isset($getdata->msg)?$getdata->msg:$getdata->isi;
				$rec = count($kontenData);
				if ($kontenData == 'Tidak ditemukan Data'){
					$this->response([
				        'status' => FALSE,
				        'message' => 'Tidak ada data'
					], REST_Controller::HTTP_NOT_FOUND);
					fclose($page);
				}else{

					$no = 0;
					foreach ($kontenData as $item) {
						$no++;
						$no_pembayaran = $item->tagihanNomor;
						
						$insertData = array(
							'idTagihan'			=> $item->tagihanNomor,
							'nomorPembayaran'	=> $item->tagihanMahasiswaNoPendaftaran,
							'nama'				=> $item->tagihanMahasiswaNama,
							'kodefakultas'		=> $item->tagihanKodeFak,
							'kodeProdi'			=> $item->tagihanKodeProdi,
							'kodePeriode'		=> $item->tagihanPeriode,
							'angkatan'			=> $item->tagihanAngkatan,
							'nominalTagihan'	=> $item->totalTagihan,
							'jenisTagihan'		=> $item->tagihanJnsPembayaran,
							'tanggalPembayaran'	=> $item->pembayaranTanggalBayar,
							'kodeUnikPembayaran'=> $item->pembayaranNoTransaksi,
							'kodeBank'			=> $item->tagihanKodeBank,
							'kanalBank'			=> $item->pembayaranKanal,
							'totalPembayaran'	=> $item->pembayaranNominal,
							'statusPembayaran'	=> '1'
						);

						$cekData = $this->api_model->getBookTIK($item->tagihanNomor);
						if ($cekData->num_rows() >= 1){
							$row = $cekData->row();
							$where = array('id'=>$row->id);
							$simpan = $this->api_model->updateBookTIK($where,$insertData);
						}else{
							$simpan = $this->api_model->insertBookTIK($insertData);
						}

						if ($simpan){
							$data_no = array();
							$data_no ['no'] = $no;
							$data_no ['nim'] = $item->tagihanMahasiswaNoPendaftaran;
							$data_no ['nama'] = $item->tagihanMahasiswaNama;
							$data_no ['kodeProdi'] = $item->tagihanKodeProdi;
							$data_no ['kodeFak'] = $item->tagihanKodeFak;
							$data_no ['tagihan'] = $item->totalTagihan;
							$data_no ['periode'] = $item->tagihanPeriode;
							$data_no ['bank'] = $item->tagihanKodeBank;
							$data_no ['waktuTransaksi'] = $item->pembayaranTanggalBayar;
							$data_no ['nominalBayar'] = $item->pembayaranNominal;
							$data_no ['status'] = 'Sudah Lunas';
							$dataArray ['data'][] = $data_no;
						}
					}
					
					fclose($page);
					$dataArray ['pesan'] = 'Berhasil Bank Bukopin';
				}

			}elseif ($bank == 'btn'){

				$url = "http://192.168.9.45/siakad_api/api/as400/pembayaranBTN/".$tgl_awal."/".$tgl_akhir;
				$data4url = '';

			    $result = $this->api_model->callAPI('GET', $url,$data4url);
			    $getdata = json_decode($result);
			    $status = isset($getdata->status)?$getdata->status:false;
				$kontenData = isset($getdata->msg)?$getdata->msg:$getdata->isi;
				$kontenData = $getdata->isi;
				//$dataArray ['data'] = $getdata;
				if ($status == false){
					$this->response([
					        'status' => FALSE,
					        'pesan' => 'Tidak ditemukan data'
					], REST_Controller::HTTP_NOT_FOUND);
					exit;		
				}
			    $no=0;
				foreach ($kontenData as $item) {
					$no++;
					$no_pembayaran = $item->tagihanId;
					$insertData = array(
						'idTagihan'			=> $item->tagihanId,
						'nomorPembayaran'	=> $item->tagihanNIM,
						'nama'				=> $item->tagihanNama,
						'kodefakultas'		=> $item->tagihanKodeFak,
						'kodeProdi'			=> $item->tagihanKodeProdi,
						'kodePeriode'		=> $item->tagihanPeriode,
						'angkatan'			=> $item->tagihanAngkatan,
						'nominalTagihan'	=> $item->tagihanJmlTarif,
						'jenisTagihan'		=> $item->pembayaranKodeBayar,
						'tanggalPembayaran'	=> $item->pembayaranTanggalBayar,
						'kodeUnikPembayaran'=> $item->pembayaranId,
						'kodeBank'			=> $item->tagihanKodeBank,
						'kanalBank'			=> $item->pembayaranChannel,
						'totalPembayaran'	=> $item->pembayaranJmlPembayaran,
						'statusPembayaran'	=> '1'
					);
					

					$cekData = $this->api_model->getBookTIK($item->tagihanId);
					if ($cekData->num_rows() >= 1){
						$row = $cekData->row();
						$where = array('id'=>$row->id);
						$simpan = $this->api_model->updateBookTIK($where,$insertData);
					}else{
						$simpan = $this->api_model->insertBookTIK($insertData);
					}

					if ($simpan){

						$data_no = array();

						$data_no ['no'] = $no;
						$data_no ['nim'] = $item->tagihanNIM;
						$data_no ['nama'] = $item->tagihanNama;
						$data_no ['kodeProdi'] = $item->tagihanKodeProdi;
						$data_no ['kodeFak'] = $item->tagihanKodeFak;
						$data_no ['tagihan'] = $item->tagihanJmlTarif;
						$data_no ['periode'] = $item->tagihanPeriode;
						$data_no ['bank'] = $item->tagihanKodeBank;
						$data_no ['waktuTransaksi'] = $item->pembayaranTanggalBayar;
						$data_no ['nominalBayar'] = $item->pembayaranJmlPembayaran;
						$data_no ['status'] = 'Sudah Lunas';

						$dataArray ['data'][] = $data_no;
					}

					
				}
				//fclose($page);
				$dataArray ['pesan'] = 'Berhasil Bank BTN';

			}else{
				
				$insertData = array(
						'idTagihan'			=> 0,
						'nomorPembayaran'	=> 0,
						'nama'				=> 0,
						'kodefakultas'		=> 0,
						'kodeProdi'			=> 0,
						'kodePeriode'		=> 0,
						'angkatan'			=> 0,
						'nominalTagihan'	=> 0,
						'jenisTagihan'		=> 0,
						'tanggalPembayaran'	=> 0,
						'kodeUnikPembayaran'=> 0,
						'kodeBank'			=> 0,
						'kanalBank'			=> 0,
						'totalPembayaran'	=> 0,
						'statusPembayaran'	=> '0'
				);
				
				$data_no = array();
				$data_no [] = '1';
				$data_no [] = '2';
				$data_no [] = '3';
				$data_no [] = '4';
				$data_no [] = '5';
				$data_no [] = '6';
				$data_no [] = '7';
				$data_no [] = '8';
				$data_no [] = '9';
				$data_no [] = '10';
				$data_no [] = '11';

				$dataArray ['data'][] = $insertData;

				$dataArray ['pesan'] = 'Tidak ada bank partner terpilih';
			}
			

			$this->set_response($dataArray, REST_Controller::HTTP_OK);
		}	
	}

	public function prosesRekonKeuangan_get()
	{
		ini_set("upload_max_filesize",25);
		ini_set('max_execution_time', 360);

		$bank = $this->uri->segment(4);
		$tglAwal = $this->uri->segment(5);
		$tglAkhir = $this->uri->segment(6);
		$dataArray = array();
		
		if ($bank=='' || $tglAwal == '' || $tglAkhir == ''){
			$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tidak ada proses'
			], REST_Controller::HTTP_NOT_FOUND);
		}else if ($tglAwal > $tglAkhir){
			$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tanggal Awal Lebih dari tanggal Akhir'
			], REST_Controller::HTTP_NOT_FOUND);
		}else{
			
			$param = array (
				'bank'	=> $bank,
				'awal'	=> $tglAwal,
				'akhir'	=> $tglAkhir
			);
			//cari data global berdasarkan parameter diatas
			$getFileGlobal = $this->api_model->getRecFileGlobal($param);
			if ($getFileGlobal->num_rows() >= 1){
				$dataArray['status'] = TRUE;
				$dataArray['data'] = array();
				$arrData = $getFileGlobal->result_array();
				$no=0;
				foreach ($arrData as $rec) {
					$no++;
					if ($rec['bank'] == 'bni'){
						$des = explode(' | ',$rec['Description']);
						$jmlBayar = $des[3];
					}else{
						$des = explode(' ',$rec['Description']);
						$jmlBayar = $des[7];
					}
					//get data total di detil
					$getFileDetil = $this->api_model->getRecFileDetil($rec['id']);
					if ($getFileDetil->num_rows() < 1){
						continue;
					}else{
						$recFD = $getFileDetil->row_array();
					}
					$keterangan = '';
					if ($jmlBayar == $recFD['jmlbayar']){
						$keterangan .= "<font color=green>Jumlah Pembayar di Global dan Detil sama</font>";
					}else{
						$keterangan .= "<font color=red>Jumlah Pembayar di Global dan Detil tidak sama</font>";
					}
					if ($rec['Credit'] == $recFD['totalbayar']){
						$keterangan .= ", <font color=green>Total Nominal Pembayaran di Global dan Detil sama</font>";
					}else{
						$keterangan .= ", <font color=red>Total Nominal Pembayaran di Global dan Detil tidak sama</font>";
					}

					$data_no = array();
					$data_no ['no'] = $no ;					
					$data_no ['bank'] = $rec['bank'] ;
					$data_no ['post_date'] = $rec['post_date'];
					$data_no ['value_date'] = $rec['value_date'];
					$data_no ['description'] = $rec['Description'];
					$data_no ['journal_no'] = $rec['journal_no'];
					$data_no ['total_nominal_global'] = 'Rp. '.number_format($rec['Credit'],0);
					$data_no ['jumlah_bayar_global'] = $jmlBayar;
					$data_no ['total_nominal_detil'] = 'Rp. '.number_format($recFD['totalbayar'],0);
					$data_no ['jumlah_bayar_detil'] = $recFD['jmlbayar'];
					$data_no ['keterangan'] = $keterangan;
					$dataArray['data'][] = $data_no;
				}
				
				$this->set_response($dataArray, REST_Controller::HTTP_OK);
			}else{
				$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tidak ada proses'
				], REST_Controller::HTTP_NOT_FOUND);
			}
		}
	}

	public function prosesRekonTIKBNI_get()
	{
		ini_set("upload_max_filesize",30);
		ini_set('max_execution_time', 3600);

		$bank = $this->uri->segment(4);
		$tanggal = $this->uri->segment(5);
		$dataArray = array();
		
		if ($bank=='' || $tanggal== ''){
			$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tidak ada proses'
			], REST_Controller::HTTP_NOT_FOUND);
		}else{
			
			$param = array (
				'bank'		=> $bank,
				'tanggal'	=> $tanggal
			);
			
			$dataArray['status'] = TRUE;
			$dataArray['data'] = array();
			//buka file detil
			$getFileDetil = $this->api_model->getRecFileDetilByDate($param);
			if ($getFileDetil->num_rows() >= 1){
				$arrDataDetil = $getFileDetil->result_array();
				foreach ($arrDataDetil as $recDetil) {
					
					if ($recDetil['nama'] == '' && $recDetil['nim'] != ''){
						$url = "http://192.168.9.45/siakad_api/api/as400/dataMahasiswa/".$recDetil['nim'];
						//echo $url;
						$data4url = '';
					    $result = $this->api_model->callAPI('GET', $url,$data4url);
					    $getdata = json_decode($result,true);
					    $status = $getdata['status'];
						if ($status){
							$nama = $getdata['isi'][0]['nama'];
						}else{
							$nama = "Tidak Dikenal Oleh Siakad";
						}
					}else{
						$nama = $recDetil['nama'];
					}
					

					$data_detil = array();
					$data_detil ['data_asal'] = $recDetil['bank'] ;
					$data_detil ['nim'] = $recDetil['nim'];
					$data_detil ['nama'] = $nama;
					$data_detil ['nominal'] = $recDetil['credit'];
					$data_detil ['tanggal_bayar'] = $recDetil['date_pay'];
					$data_detil ['non_ukt'] = "<input type=\"checkbox\" id=\"non_ukt\" name=\"non_ukt\" onclick=\"ubahUKT('".$recDetil['id']."')\"> Non UKT/SPU";
					$dataArray['data'][] = $data_detil;
				}
			}else{
				$message = [
		            'status' => false,
		            'message' => 'Data BNI tidak ada'
		        ];

		        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT);
			}

			//buka file TIK
			$getFileTIK = $this->api_model->getRecFileTIKByDate($param);
			if ($getFileTIK->num_rows() >= 1){
				$arrDataTIK = $getFileTIK->result_array();
				foreach ($arrDataTIK as $recTIK) {
					
					$data_tik = array();
					$data_tik ['data_asal'] = 'TIK';
					$data_tik ['nim'] = $recTIK['nomorPembayaran'];
					$data_tik ['nama'] = $recTIK['nama'];
					$data_tik ['nominal'] = $recTIK['totalPembayaran'];
					$data_tik ['tanggal_bayar'] = substr($recTIK['tanggalPembayaran'],0,10);
					$data_detil ['non_ukt'] = "";
					$dataArray['data'][] = $data_tik;
				}
			}else{
				$message = [
		            'status' => false,
		            'message' => 'Data TIK tidak ada'
		        ];

		        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT);
			}
			
			$this->set_response($dataArray, REST_Controller::HTTP_OK);
		}
	}

	public function prosesRekonTIKBNIRangeDate_get()
	{
		ini_set("upload_max_filesize",30);
		ini_set('max_execution_time', 3600);

		$bank = $this->uri->segment(4);
		$tanggal0 = $this->uri->segment(5);
		$tanggal1 = $this->uri->segment(6);
		$dataArray = array();
		
		if ($bank=='' || $tanggal0 == '' || $tanggal1 == ''){
			$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tidak ada proses'
			], REST_Controller::HTTP_NOT_FOUND);
		}else{
			
			$param = array (
				'bank'		=> $bank,
				'tanggal0'	=> $tanggal0,
				'tanggal1'	=> $tanggal1
			);
			
			$dataArray['status'] = TRUE;
			$dataArray['data'] = array();
			//buka file detil
			$getFileDetil = $this->api_model->getRecFileDetilByDateRange($param);
			if ($getFileDetil->num_rows() >= 1){
				$arrDataDetil = $getFileDetil->result_array();
				foreach ($arrDataDetil as $recDetil) {
					/*
					if ($recDetil['nama'] == '' && $recDetil['nim'] != ''){
						$url = "http://192.168.9.45/siakad_api/api/as400/dataMahasiswa/".$recDetil['nim'];
						//echo $url;
						$data4url = '';
					    $result = $this->api_model->callAPI('GET', $url,$data4url);
					    $getdata = json_decode($result,true);
					    $status = $getdata['status'];
						if ($status){
							$nama = $getdata['isi'][0]['nama'];
						}else{
							$nama = "Tidak Dikenal Oleh Siakad";
						}
					}else{
						$nama = $recDetil['nama'];
					}
					*/
					$nama = $recDetil['nama'];

					$data_detil = array();
					$data_detil ['data_asal'] = $recDetil['bank'] ;
					$data_detil ['nim'] = trim($recDetil['nim']);
					$data_detil ['nama'] = $nama;
					$data_detil ['nominal'] = number_format($recDetil['credit']);
					$data_detil ['tanggal_bayar'] = $recDetil['date_pay'];
					$data_detil ['non_ukt'] = "<input type=\"checkbox\" id=\"non_ukt\" name=\"non_ukt\" onclick=\"ubahUKT('".$recDetil['id']."')\"> Non UKT/SPU";
					$dataArray['data'][] = $data_detil;
				}
			}else{
				$message = [
		            'status' => false,
		            'message' => 'Data BNI tidak ada'
		        ];

		        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT);
			}
			
			//buka file TIK
			$getFileTIK = $this->api_model->getRecFileTIKByDateRange($param);
			if ($getFileTIK->num_rows() >= 1){
				$arrDataTIK = $getFileTIK->result_array();
				foreach ($arrDataTIK as $recTIK) {
					
					$data_tik = array();
					$data_tik ['data_asal'] = 'TIK';
					$data_tik ['nim'] = $recTIK['nomorPembayaran'];
					$data_tik ['nama'] = $recTIK['nama'];
					$data_tik ['nominal'] = number_format($recTIK['totalPembayaran']);
					$data_tik ['tanggal_bayar'] = substr($recTIK['tanggalPembayaran'],0,10);
					$data_detil ['non_ukt'] = "";
					$dataArray['data'][] = $data_tik;
				}
			}else{
				$message = [
		            'status' => false,
		            'message' => 'Data TIK tidak ada'
		        ];

		        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT);
			}
			
			$this->set_response($dataArray, REST_Controller::HTTP_OK);
		}
	}

	public function prosesRekonJumlahTIKBNI_get()
	{
		ini_set("upload_max_filesize",25);
		ini_set('max_execution_time', 360);

		$bank = $this->uri->segment(4);
		$tanggalAwal = $this->uri->segment(5);
		$tanggalAkhir = $this->uri->segment(6);
		$dataArray = array();
		
		if ($bank=='' || $tanggalAwal== '' || $tanggalAkhir== ''){
			$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tidak ada proses'
			], REST_Controller::HTTP_NOT_FOUND);
		}else if ($tanggalAwal > $tanggalAkhir){
			$this->response([
			        'status' => FALSE,
			        'pesan' => 'Tanggal Awal Lebih dari tanggal Akhir'
			], REST_Controller::HTTP_NOT_FOUND);
		}else{
			
			$dataArray['status'] = TRUE;
			$dataArray['data'] = array();
			
			$startTime = strtotime( $tanggalAwal );
			$endTime = strtotime( $tanggalAkhir );

			$keterangan = '';
			$no = 0;
			// Loop between timestamps, 24 hours at a time
			for ( $i = $startTime; $i <= $endTime; $i = $i + 86400 ) {
				$no++;
				$thisDate = date( 'Y-m-d', $i ); // 2010-05-01, 2010-05-02, etc
				$param = array (
					'bank'	=> $bank,
					'tanggal'	=> $thisDate
				);
				//cari file detil
				$getFileDetil = $this->api_model->getJmlFileDetilByDate($param);
				if ($getFileDetil->num_rows() >= 1){
					$recBank = $getFileDetil->row();
					$jmlBank = $recBank->jmlbayar;
					$nomBank = $recBank->nominalbayar;	
				}else{
					$jmlBank = 0;
					$nomBank = 0;	
				}

				//buka file TIK
				$bank = strtoupper($bank);
				$getFileTIK = $this->api_model->getJmlFileTIKByDate($thisDate,$bank);
				if ($getFileTIK->num_rows() >= 1){
					$recTIK = $getFileTIK->row();
					$jmlTIK = $recTIK->jmlbayar;
					$nomTIK = $recTIK->nominalbayar;	
				}else{
					$jmlTIK = 0;
					$nomTIK = 0;
				}

				if ($jmlBank != $jmlTIK &&  $nomBank != $nomTIK){
					$keterangan = 'Jumlah dan Nominal Pembayaran tidak cocok';
				}elseif ($jmlBank == $jmlTIK &&  $nomBank != $nomTIK){
					$keterangan = 'Nominal Pembayaran tidak cocok';
				}elseif ($jmlBank != $jmlTIK &&  $nomBank == $nomTIK){
					$keterangan = 'Jumlah Pembayaran tidak cocok';
				}else{
					$keterangan = '';
				}

				$data_jml = array();
				$data_jml ['no'] = $no;
				$data_jml ['tanggal'] = $thisDate;
				$data_jml ['jmlBank'] = number_format($jmlBank,0);
				$data_jml ['nomBank'] = number_format($nomBank,0);
				$data_jml ['jmlTIK'] = number_format($jmlTIK,0);
				$data_jml ['nomTIK'] = number_format($nomTIK,0);
				$data_jml ['keterangan'] = $keterangan;
					
				$dataArray['data'][] = $data_jml;
				$keterangan = '';
			}

			$this->set_response($dataArray, REST_Controller::HTTP_OK);
		}
	}
}

