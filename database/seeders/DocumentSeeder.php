<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Document::query()->create([
            'title' => 'Documentación para el Inquilino',
            'description' => '123456789',
            'file_path' => 'https://example.com/document.pdf',
            'original_filename' => 'Document name',
            'active' => true,
            'user_id' => 1,
        ]);
        Document::query()->create([
            'title' => 'Cedula de Ciudadania',
            'description' => '123456789',
            'file_path' => 'https://example.com/document.pdf',
            'original_filename' => 'Document name',
            'active' => true,
            'user_id' => 1,
        ]);
        Document::query()->create([
            'title' => 'Lista de Documentos para Arrendatarios',
            'description' => '123456789',
            'file_path' => 'https://example.com/document.pdf',
            'original_filename' => 'Document name',
            'active' => true,
            'user_id' => 1,
        ]);
        Document::query()->create([
            'title' => 'Información Relevante para el Inquilino',
            'description' => '123456789',
            'file_path' => 'https://example.com/document.pdf',
            'original_filename' => 'Document name',
            'active' => true,
            'user_id' => 1,
        ]);
    }
}
