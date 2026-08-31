<?php

namespace Tests\Unit;

use App\Models\PolicyTemplate;
use App\Services\PolicyTemplate\PolicyTemplateService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class PolicyTemplateServiceTest extends TestCase
{
    private string $validTemplateContents;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validTemplateContents = file_get_contents(
            storage_path('app/templates/policy_template.docx')
        );

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('policy_templates', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->string('version')->unique();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });

        Storage::fake('local');
        Storage::disk('local')->put(
            PolicyTemplateService::LEGACY_TEMPLATE_PATH,
            $this->validTemplateContents
        );

        PolicyTemplate::create([
            'filename' => 'policy_template.docx',
            'path' => PolicyTemplateService::LEGACY_TEMPLATE_PATH,
            'version' => '1.0',
        ]);
    }

    public function test_returns_the_current_template_and_required_variables(): void
    {
        $result = (new PolicyTemplateService())->current();

        $this->assertTrue($result['status']);
        $this->assertSame('1.0', $result['data']['template']->version);
        $this->assertContains('contract_number', $result['data']['required_variables']);
        $this->assertContains('corresponding_phone', $result['data']['required_variables']);
    }

    public function test_stores_a_valid_docx_as_the_next_version(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'novo-template.docx',
            $this->validTemplateContents
        );
        $request = Request::create('/api/policy-template', 'POST', files: ['document' => $file]);

        $result = (new PolicyTemplateService())->store($request);

        $this->assertTrue($result['status'], $result['error'] ?? '');
        $this->assertSame('2.0', $result['data']['template']->version);
        $this->assertSame('novo-template.docx', $result['data']['template']->filename);
        Storage::disk('local')->assertExists($result['data']['template']->path);
    }

    public function test_rejects_an_invalid_docx(): void
    {
        $file = UploadedFile::fake()->create(
            'template-invalido.docx',
            10,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        $request = Request::create('/api/policy-template', 'POST', files: ['document' => $file]);

        $result = (new PolicyTemplateService())->store($request);

        $this->assertFalse($result['status']);
        $this->assertSame(422, $result['statusCode']);
        $this->assertSame(1, PolicyTemplate::count());
    }

    public function test_rejects_a_docx_without_all_required_variables(): void
    {
        $phpWord = new PhpWord();
        $phpWord->addSection()->addText('${contract_number}');
        $temporaryBasePath = tempnam(sys_get_temp_dir(), 'policy-template-');
        $temporaryPath = $temporaryBasePath.'.docx';
        unlink($temporaryBasePath);
        IOFactory::createWriter($phpWord)->save($temporaryPath);

        $file = UploadedFile::fake()->createWithContent(
            'template-incompleto.docx',
            file_get_contents($temporaryPath)
        );
        unlink($temporaryPath);

        $request = Request::create('/api/policy-template', 'POST', files: ['document' => $file]);
        $result = (new PolicyTemplateService())->store($request);

        $this->assertFalse($result['status']);
        $this->assertSame(422, $result['statusCode']);
        $this->assertStringContainsString('date', $result['error']);
        $this->assertSame(1, PolicyTemplate::count());
    }

    public function test_resolves_the_current_template_path(): void
    {
        $path = (new PolicyTemplateService())->currentPath();

        $this->assertSame(
            Storage::disk('local')->path(PolicyTemplateService::LEGACY_TEMPLATE_PATH),
            $path
        );
    }
}
