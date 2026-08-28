<?php

namespace Tests\Unit;

use App\Models\TermDocument;
use App\Services\TermDocument\TermDocumentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TermDocumentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('term_documents', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path')->nullable();
            $table->text('external_url')->nullable();
            $table->string('version')->unique();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_returns_the_current_term(): void
    {
        TermDocument::create([
            'filename' => 'termo.pdf',
            'external_url' => 'https://example.com/termo.pdf',
            'version' => '1.0',
        ]);

        $result = (new TermDocumentService())->current();

        $this->assertTrue($result['status']);
        $this->assertSame('1.0', $result['data']->version);
        $this->assertSame('https://example.com/termo.pdf', $result['data']->url);
    }

    public function test_stores_a_pdf_as_the_next_version(): void
    {
        Storage::fake('public');
        TermDocument::create([
            'filename' => 'termo.pdf',
            'external_url' => 'https://example.com/termo.pdf',
            'version' => '1.0',
        ]);

        $file = UploadedFile::fake()->create('novo-termo.pdf', 100, 'application/pdf');
        $request = Request::create('/api/term', 'POST', files: ['document' => $file]);

        $result = (new TermDocumentService())->store($request);

        $this->assertTrue($result['status']);
        $this->assertSame('2.0', $result['data']->version);
        $this->assertSame('novo-termo.pdf', $result['data']->filename);
        Storage::disk('public')->assertExists($result['data']->path);
    }
}
