<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mdlaporan extends CI_Model {


	function build_kriteria($kriteria){
		if(is_array($kriteria)){
			$arr = array();
			foreach($kriteria as $k){
				array_push($arr,$this->run_krit($k));
			}
			$ret = implode(" OR ", $arr);
			return $ret;
		}
		else
			return $this->run_krit($kriteria);
	}

	function run_krit($item){
		return "kd_barang LIKE ".$this->db->escape("$item%");
	}


	public function ex($tg_a,$tg_b,$divisi="",$kriteria=null){
		$indos_1 = $this->def->indo_date($tg_a);
		$indos_2 = $this->def->indo_date($tg_b);

		$indo_1 = date("Ymd",strtotime($tg_a));
		$indo_2 = date("Ymd",strtotime($tg_b));

		if($tg_a == $tg_b){
			$out = $divisi." ".$indo_1;
			$outs = $indos_1;
		}
		else{
			$out = $divisi." $indo_1 - $indo_2";
			$outs = "$indos_1 - $indos_2";
		}


		//PHP Excel Properties
		include "ExcelClass/PHPExcel.php";
		$ex = new PHPExcel();
		//set Document Properties
		$ex->getProperties()->setCreator("Christian Rosandhy")
							->setLastModifiedBy("Christian Rosandhy")
							->setTitle("Skor Produk Unggulan");

		$adaa = "";
		if($divisi <> "")
			$adaa = "Divisi $divisi ";

		$ex ->setActiveSheetIndex(0)
			->setCellValue("A1","Laporan Skor Penjualan Produk Unggulan")
			->setCellValue("A2",$adaa."per tanggal $outs")
			->setCellValue("A3","No")
			->setCellValue("B3","Nama Karyawan")
			->setCellValue("C3","Kode Sales")
			->setCellValue("D3","Divisi")
			->setCellValue("E3","Alamat")
			->setCellValue("F3","Telepon")
			->setCellValue("G3","Skor");

		$addDv = "";
		if($divisi <> "")
			$addDv = "divisi = ".$this->db->escape($divisi)." AND ";

		$query = $this->db->query("SELECT * FROM tb_karyawan WHERE $addDv stat <> 0");

		$c = 5;
		$no = 1;
		foreach($query->result_array() as $row){

			$kr = "";
			if($kriteria <> "" and !is_null($kriteria)){
				$kr = $this->build_kriteria($kriteria);
			}

			$nilai = $this->get_score($row['kd_sales'],$row['divisi'],$tg_a,$tg_b,$kr);

			$ex ->setActiveSheetIndex(0)
				->setCellValue("A$c",$no)
				->setCellValue("B$c",$row['nama'])
				->setCellValue("C$c",$row['kd_sales'])
				->setCellValue("D$c",$row['divisi'])
				->setCellValue("E$c",$row['alamat'])
				->setCellValue("F$c",$row['telp'])
				->setCellValue("G$c",$nilai);
			$no++;
			$c++;
		}

		$ex->getActiveSheet()->setTitle("Skor Produk Unggulan");
		$ex->setActiveSheetIndex(0);
		//Redirecting to Save
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="SkorPU '.$out.' .xlsx"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$objWriter = PHPExcel_IOFactory::createWriter($ex, 'Excel2007');
		$objWriter->save('php://output');	
	}


	function get_karyawan($id){
		$query = $this->db->query("SELECT * FROM tb_karyawan WHERE id = ".$this->db->escape($id));
		return $query->result_array();
	}

	function get_karyawans($divisi, $kd_sales){
		$query = $this->db->query("SELECT * FROM tb_karyawan WHERE divisi = ".$this->db->escape($divisi)." AND kd_sales = ".$this->db->escape($kd_sales));
		return $query->row_array()['nama'];
	}



	function create_detail($kd_sales,$divisi, $tga, $tgb, $krit=""){

		$kt = "";
		if($krit <> "")
			$kt = "AND (" . $this->build_kriteria($krit) .") ";

		$query = $this->db->query("SELECT * FROM tb_history_jual WHERE kd_sales = ".$this->db->escape($kd_sales)." AND divisi = ".$this->db->escape($divisi)." AND tgl BETWEEN ".$this->db->escape($tga)." AND ". $this->db->escape($tgb)." AND skor > 0 $kt");
		return $query->result_array();
	}

	function get_nama_barang($kd_barang){
		$q = $this->db->query("SELECT nm_barang FROM tb_penjualan WHERE kd_barang = ".$this->db->escape($kd_barang));
		return $q->row_array();
	}










	public function get_rule(){
		$this->db->where("stat",1);
		$this->db->select("id, rule_name");
		$query = $this->db->get("tb_kriteria");
		return $query->result_array();
	}

	public function get_rule_list($tgl_a, $tgl_b, $filter){

		$krit = array();
		if(count($filter) > 0){
			foreach($filter as $f){
				$krit[] = $f;
			}
		}
		else{
			$krit = array($filter);
		}


		$this->db->where("tgl BETWEEN '$tgl_a' AND '$tgl_b'");
		$n = 0;

		$group = "(";
		$artmp = array();
		foreach($krit as $kr){
			array_push($artmp,"kd_barang LIKE '%$kr%'");
		}
		$imp = implode(" OR ",$artmp);
		$group .= $imp;
		$group .=")";
		

		$this->db->where($group);
		$query = $this->db->get("tb_history_jual");
		return $query->result_array();
	}



	public function get_divisi(){
		$sql = "SELECT DISTINCT divisi FROM tb_karyawan ORDER BY divisi";
		$run = $this->db->query($sql);
		$arr = array();
		foreach($run->result_array() as $row){
			$arr[] = $row['divisi'];
		}
		return $arr;
	}

	public function detail_score($kd_sales, $divisi, $tgl_a, $tgl_b){
		$sql = "
		SELECT
			kd_sales, tgl, divisi, kd_barang, SUM(jml) AS jml, SUM(skor) AS skor
		FROM `tb_history_jual` 
		WHERE 
			kd_sales = ".intval($kd_sales)." AND divisi = ".$this->db->escape($divisi)."
		    AND tgl BETWEEN ".$this->db->escape($tgl_a)." AND ".$this->db->escape($tgl_b)."
		GROUP BY kd_barang, tgl
		ORDER BY kd_barang, tgl
		";

		$run = $this->db->query($sql);
		return $run->result_array();
	}


	public function get_score($tgl_a, $tgl_b, $divisi){
		$ifand = "";
		if(strlen($divisi) > 0){
			$ifand = "AND a.divisi = ".$this->db->escape($divisi);
		}

		$sql = "
		SELECT 
			a.kd_sales, a.divisi, SUM(a.skor) AS total_skor 
		FROM tb_history_jual a 
		WHERE a.tgl BETWEEN ".$this->db->escape($tgl_a)." AND ".$this->db->escape($tgl_b)."
		$ifand
		GROUP BY a.divisi, a.kd_sales
		ORDER BY SUM(skor) DESC
		";

		$run = $this->db->query($sql);
		return $run->result_array();
	}

	public function get_max_score($tgl_a, $tgl_b, $divisi){
		$ifand = "";
		if(strlen($divisi) > 0){
			$ifand = "AND a.divisi = ".$this->db->escape($divisi);
		}
		$sql = "
		SELECT SUM(a.skor) AS total_skor FROM tb_history_jual a
		WHERE a.tgl BETWEEN ".$this->db->escape($tgl_a)." AND ".$this->db->escape($tgl_b)."
		$ifand
		GROUP BY a.divisi, a.kd_sales
		ORDER BY SUM(a.skor) DESC
		LIMIT 1
		";

		$run = $this->db->query($sql);
		$row = $run->row_array();
		return $row['total_skor'];
	}

	public function karyawan_array(){
		$sql = $this->db->query("SELECT * FROM tb_karyawan WHERE stat = 1");
		$arr = array();
		foreach($sql->result_array() as $row){
			$arr[$row['divisi']][$row['kd_sales']] = $row['nama'];
		}
		return $arr;
	}

	public function better_division($input){
		$list = array(
			"CW1" => "Citra Warna 1",
			"CW2" => "Citra Warna 2",
			"CW3" => "Citra Warna 3",
			"CW4" => "Citra Warna 4",
			"CW5" => "Citra Warna 5",
			"CW6" => "Citra Warna 6",
			"CW7" => "Citra Warna 7",
			"CW8" => "Citra Warna 8",
			"CW9" => "Citra Warna 9",
			"CA0" => "Citra Warna 10",
			"CA1" => "Citra Warna 11",
			"CA2" => "Citra Warna 12",
			"CA3" => "Citra Warna 13",
			"CA4" => "Citra Warna 14",
			"CA5" => "Citra Warna 15",
			"CA6" => "Citra Warna 16",
			"CA7" => "Citra Warna 17",
			"CA8" => "Citra Warna 18",
			"CA9" => "Citra Warna 19"
		);

		if(isset($list[$input]))
			return $list[$input];
		return false;
	}

	public function better_code($kd_barang){
		$sql = $this->db->query("SELECT nm_barang FROM tb_kode WHERE kd_barang = ".$this->db->escape($kd_barang));
		if($sql->num_rows() == 0)
			return $kd_barang;
		else{
			$row = $sql->row_array();
			return $row['nm_barang'];
		}

	}

	public function better_tgl(){
		$sql = "SELECT DISTINCT tgl FROM tb_history_jual ORDER BY tgl DESC LIMIT 1";
		$run = $this->db->query($sql);
		$row = $run->row_array();
		return $row['tgl'];
	}

}