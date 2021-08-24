<?php
namespace App\Controllers\Admin;

use App\Models\Admin\ModelTransaksi;
use App\Controllers\BaseController;
class Transaksi extends BaseController {

    private $array_obat = [];

    public function __construct()
    {
        $this->ModelTransaksi = new ModelTransaksi();
		helper('form');
    }

    public function index()
	{
        $data = [
            'title' => 'Data Transaksi',
            'datatransaksi' => $this->ModelTransaksi->get_all()
        ];
        // print_r($data['transaksi']);
        return view('admin/data_transaksi', $data);
    }
    
    public function tambah()
    {
        $this->model('Obat_model');
        $data['obat'] = $this->Obat_model->get_all();
        $this->layout->set_title('Tambah transaksi');
        return $this->layout->load('template', 'transaksi/tambah', $data);
    }

    public function store()
    {
        $this->form_validation->set_rules('nama_pembeli', 'Nama Pembeli', 'required|trim|alpha_numeric_spaces');
        $this->form_validation->set_rules('data_obat', 'Obat', 'callback__data_obat_check');
        if ($this->form_validation->run() == FALSE)
        {
            $response = [
                'status' => false,
                'message' => 'form error',
                'error' => validation_errors('<div class="alert alert-danger">', '</div>'),
            ];
            echo json_encode($response);
            return;
        }
        $data_transaksi = [
            'tgl' => date('Y-m-d h:i:s'),
            'nama_pembeli' => $this->input->post('nama_pembeli'),
            'admin_id' => $this->session->userdata('user_id'),
        ];
        $tambah = $this->ModelTransaksi->create($data_transaksi);
        $transaksi_id = $this->db->insert_id();

        $detail_transaksi = [];
        foreach ($this->array_obat as $key => $ob) {
            $detail_transaksi[$key] = [
                'transaksi_id' => $transaksi_id,
                'kode_obat' => $ob->kode,
                'jumlah' => $ob->jumlah,
            ];
        }
        $this->ModelTransaksi->create_detail($detail_transaksi);
        $msg = $tambah ? 'Berhasil ditambah' : 'Gagal ditambah';
        $response = [
            'status' => true,
            'message' => $msg,
        ];
        echo json_encode($response);
        return;
    }

    public function _data_obat_check($value)
    {
        $this->array_obat = json_decode($value);
        if (empty($this->array_obat)) 
        {
            $this->form_validation->set_message('_data_obat_check', 'The {field} can not be empty');
            return false;
        }
        foreach ($this->array_obat as $ob) 
        {
            $obat = $this->db->get_where('obat', ['kode_obat' => $ob->kode])->row();
            if (! $obat) 
            {
                $this->form_validation->set_message('_data_obat_check', 'Data {field} tidak ditemukan');
                return false;
            }
            if ((int)$obat->stok < $ob->jumlah) 
            {
                $this->form_validation->set_message('_data_obat_check', "Gagal!, Stok {$obat->nama_obat} tersisa {$obat->stok} anda menginput {$ob->jumlah}");
                return false;
            }
        }
        return true;
    }

    public function hapus($id = null)
    {
        if (! $id) return show_404();
        $this->db->delete('transaksi', ['id' => $id]);
        $this->session->set_flashdata('info', 'Berhasil dihapus');
        redirect('transaksi');
    }
}