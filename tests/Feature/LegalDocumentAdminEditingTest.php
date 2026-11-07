<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalDocuments\Pages\CreateLegalDocument;
use App\Filament\Resources\LegalDocuments\Pages\EditLegalDocument;
use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class LegalDocumentAdminEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_seeded_document_saves_without_duplicate_key_error(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $this->actingAs($owner);

        $record = LegalDocument::where('key', 'privacy_policy')->firstOrFail();

        Livewire::test(EditLegalDocument::class, ['record' => $record->getRouteKey()])
            ->assertFormSet(['key' => 'privacy_policy'])
            ->fillForm(['title' => 'Политика конфиденциальности (ред.)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Политика конфиденциальности (ред.)', $record->fresh()->title);
    }

    public function test_creating_a_document_opens_a_working_edit_page(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $this->actingAs($owner);

        Livewire::test(CreateLegalDocument::class)
            ->fillForm([
                'key' => 'politics',
                'slug' => 'politics',
                'title' => 'Политика конфиденциальности',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $record = LegalDocument::where('key', 'politics')->firstOrFail();
        $this->assertSame('politics', $record->slug);

        // Раньше при слаге с «/» редирект после создания вёл на
        // /admin/legal-documents/{slug}/edit и Laravel отдавал 404.
        $this->get('/admin/legal-documents/'.$record->getRouteKey().'/edit')
            ->assertOk();
    }

    public function test_slug_with_path_separator_is_rejected_by_the_form(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $this->actingAs($owner);

        Livewire::test(CreateLegalDocument::class)
            ->fillForm([
                'key' => 'politics',
                'slug' => 'dokumenty/politics',
                'title' => 'Политика конфиденциальности',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        $this->assertDatabaseMissing('legal_documents', ['key' => 'politics']);
    }

    public function test_model_normalizes_path_like_slug_for_programmatic_creation(): void
    {
        $document = LegalDocument::create([
            'key' => 'politics',
            'slug' => 'dokumenty/politics',
            'title' => 'Политика конфиденциальности',
        ]);

        $this->assertSame('politics', $document->fresh()->slug);

        $this->get(route('legal.show', $document->slug))
            ->assertOk()
            ->assertSee('Политика конфиденциальности');
    }

    public function test_document_created_in_admin_appears_in_footer(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $this->actingAs($owner);

        Livewire::test(CreateLegalDocument::class)
            ->fillForm([
                'key' => 'cookie_policy',
                'slug' => 'cookie-policy',
                'title' => 'Политика использования cookie',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Раньше футер выводил только жёстко заданный список ключей, поэтому
        // документ, созданный через админку, в блоке «Правовая информация» не появлялся.
        $this->get('/')
            ->assertOk()
            ->assertSee('Политика использования cookie')
            ->assertSee(route('legal.show', 'cookie-policy'), false);
    }

    public function test_technical_key_with_surrounding_spaces_is_trimmed(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $this->actingAs($owner);

        Livewire::test(CreateLegalDocument::class)
            ->fillForm([
                'key' => '  marketing_policy  ',
                'slug' => 'marketing-policy',
                'title' => 'Политика маркетинга',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Пробел в конце ключа ломал поиск документа по ключу (legal('marketing_policy')).
        $this->assertSame('marketing_policy', LegalDocument::where('slug', 'marketing-policy')->firstOrFail()->key);
    }

    public function test_saving_an_untouched_seeded_document_keeps_its_html(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $this->actingAs($owner);

        $record = LegalDocument::where('key', 'privacy_policy')->firstOrFail();
        $originalBody = (string) $record->body;

        Livewire::test(EditLegalDocument::class, ['record' => $record->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($originalBody, (string) $record->fresh()->body);
    }

    public function test_body_is_edited_with_the_rich_editor_and_preserves_supported_html(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $this->actingAs($owner);

        $record = LegalDocument::where('key', 'privacy_policy')->firstOrFail();

        $html = <<<'HTML'
<h2>Заголовок раздела</h2>
<p>Текст с <strong>акцентом</strong> и <a href="https://example.com">ссылкой</a>.</p>
<ul><li>Первый пункт</li><li>Второй пункт</li></ul>
<table><tbody><tr><td>Ячейка</td><td>Значение</td></tr></tbody></table>
<script>alert('xss')</script>
HTML;

        Livewire::test(EditLegalDocument::class, ['record' => $record->getRouteKey()])
            ->fillForm(['body' => $html])
            ->call('save')
            ->assertHasNoFormErrors();

        $body = (string) $record->fresh()->body;

        $this->assertStringContainsString('<h2>Заголовок раздела</h2>', $body);
        $this->assertStringContainsString('<strong>акцентом</strong>', $body);
        $this->assertStringContainsString('href="https://example.com"', $body);
        $this->assertStringContainsString('<ul>', $body);
        $this->assertStringContainsString('<li>', $body);
        $this->assertStringContainsString('Первый пункт', $body);
        $this->assertStringContainsString('<table>', $body);
        $this->assertStringContainsString('<td>Ячейка</td>', $body);
        $this->assertStringNotContainsString('<script', $body);
    }

    public function test_edit_page_renders_the_rich_editor_for_the_body_field(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $record = LegalDocument::where('key', 'privacy_policy')->firstOrFail();

        $this->actingAs($owner)
            ->get('/admin/legal-documents/'.$record->getRouteKey().'/edit')
            ->assertOk()
            ->assertSee('fi-fo-rich-editor', false)
            ->assertSee('fi-fo-rich-editor-content', false);
    }

    public function test_public_page_sanitizes_legacy_unsafe_body(): void
    {
        $document = LegalDocument::create([
            'key' => 'legacy_policy',
            'slug' => 'legacy-policy',
            'title' => 'Старый документ',
        ]);

        // Имитируем запись, сохранённую до включения санитайзера: обходим модель.
        DB::table('legal_documents')->where('id', $document->id)->update([
            'body' => '<p>Безопасный текст</p><script>alert(1)</script><img src="x" onerror="alert(1)">',
        ]);

        $this->get(route('legal.show', 'legacy-policy'))
            ->assertOk()
            ->assertSee('Безопасный текст')
            ->assertDontSee('alert(1)', false)
            ->assertDontSee('onerror', false);
    }
}
