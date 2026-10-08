<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTbAnggota extends Migration
{
    public function up()
    {
        // Ganti nama kolom pr_number → nip
        $this->forge->modifyColumn('tb_anggota', [
            'pr_number' => [
                'name'       => 'nip',
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
            ],
        ]);

        // Tambah kolom departemen, section, shift
        $fields = [
            'departemen' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'nama',
            ],
            'section' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'departemen',
            ],
            'shift' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => true,
                'after'      => 'section',
            ],
        ];
        $this->forge->addColumn('tb_anggota', $fields);
    }

    public function down()
    {
        $this->forge->modifyColumn('tb_anggota', [
            'nip' => [
                'name'       => 'pr_number',
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
            ],
        ]);
        $this->forge->dropColumn('tb_anggota', ['departemen', 'section', 'shift']);
    }
}
