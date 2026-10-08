<?php

namespace App\Models;

use CodeIgniter\Model;

class DoorprizeItemModel extends Model
{
    protected $table         = 'tb_doorprize_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['id_rat', 'nama_barang', 'jumlah', 'kategori', 'gambar_path', 'created_at'];
    protected $useTimestamps = false;

    public function getByRat(int $idRat): array
    {
        return $this->where('id_rat', $idRat)->orderBy('kategori', 'ASC')->orderBy('nama_barang', 'ASC')->findAll();
    }

    public function sisaBelumDiundi(int $idRat): array
    {
        $db = \Config\Database::connect();
        $sudahDiundi = $db->table('tb_doorprize_winners')
            ->select('id_item, COUNT(*) as jumlah_diundi')
            ->where('id_rat', $idRat)
            ->groupBy('id_item')
            ->get()->getResultArray();

        $mapDiundi = [];
        foreach ($sudahDiundi as $row) {
            $mapDiundi[$row['id_item']] = (int) $row['jumlah_diundi'];
        }

        $items = $this->getByRat($idRat);
        $sisa = [];
        foreach ($items as $item) {
            $diundi = $mapDiundi[$item['id']] ?? 0;
            $remaining = $item['jumlah'] - $diundi;
            if ($remaining > 0) {
                $item['sisa'] = $remaining;
                $item['sudah_diundi'] = $diundi;
                $sisa[] = $item;
            }
        }
        return $sisa;
    }
}
