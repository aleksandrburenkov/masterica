<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LegalDocument;
use App\Models\User;
use App\Support\LegalDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegalDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @return array<string, array{string}> */
    public static function requiredKeys(): array
    {
        return [
            'privacy_policy' => ['privacy_policy'],
            'consent_personal_data' => ['consent_personal_data'],
            'booking_rules' => ['booking_rules'],
            'offer_terms' => ['offer_terms'],
            'license_terms' => ['license_terms'],
            'outreach_terms' => ['outreach_terms'],
            'review_consent' => ['review_consent'],
            'platform_terms' => ['platform_terms'],
            'consent_registration' => ['consent_registration'],
            'consent_booking' => ['consent_booking'],
            'consent_checkout' => ['consent_checkout'],
            'consent_outreach' => ['consent_outreach'],
            'consent_contacts' => ['consent_contacts'],
            'consent_review' => ['consent_review'],
            'consent_marketing' => ['consent_marketing'],
        ];
    }

    #[DataProvider('requiredKeys')]
    public function test_required_document_exists_and_is_production_ready(string $key): void
    {
        $document = LegalDocument::where('key', $key)->firstOrFail();

        $this->assertTrue($document->is_production_ready);
        $this->assertNotSame('', trim((string) $document->lead));
    }

    public function test_production_texts_contain_no_placeholder_markers(): void
    {
        $forbidden = ['демонстрацион', 'заглушк', 'TODO', 'демо-текст', 'локальной версии'];

        foreach (LegalDocument::all() as $document) {
            $haystack = mb_strtolower($document->title.' '.$document->lead.' '.$document->body);
            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    mb_strtolower($needle),
                    $haystack,
                    "Документ {$document->key} содержит недопустимый фрагмент «{$needle}».",
                );
            }
        }
    }

    public function test_public_legal_pages_render_persisted_documents(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Политика в отношении обработки персональных данных')
            ->assertSee('152-ФЗ');

        $this->get(route('legal.consent'))
            ->assertOk()
            ->assertSee('Согласие на обработку персональных данных');

        $this->get(route('legal.rules'))
            ->assertOk()
            ->assertSee('Правила записи на мастер-классы и оформления заказов');
    }

    public function test_generic_document_route_renders_offer_and_license(): void
    {
        $this->get(route('legal.show', 'publichnaya-oferta'))
            ->assertOk()
            ->assertSee('Публичная оферта');

        $this->get(route('legal.show', 'licenzionnoe-soglashenie'))
            ->assertOk()
            ->assertSee('Лицензионное соглашение');
    }

    public function test_forms_expose_required_consent_checkboxes_with_document_links(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="consent_data"', false)
            ->assertSee('required', false)
            ->assertSee('Условия обработки');

        $this->get(route('outreach.index'))
            ->assertOk()
            ->assertSee('name="consent_data"', false)
            ->assertSee('name="consent_marketing"', false)
            ->assertSee('target="_blank"', false);
    }

    public function test_footer_links_to_all_key_documents(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('legal.show', 'politika-konfidencialnosti'), false)
            ->assertSee(route('legal.show', 'publichnaya-oferta'), false)
            ->assertSee('152-ФЗ');
    }

    public function test_owner_can_manage_legal_documents(): void
    {
        $owner = User::where('email', 'owner@korni.local')->firstOrFail();

        $this->actingAs($owner)
            ->get('/admin/legal-documents')
            ->assertOk()
            ->assertSee('Политика в отношении обработки персональных данных');
    }

    public function test_manager_cannot_manage_legal_documents(): void
    {
        $manager = User::where('email', 'manager@korni.local')->firstOrFail();

        $this->actingAs($manager)->get('/admin/legal-documents')->assertForbidden();
    }

    public function test_saving_document_flushes_cache_and_updates_public_page(): void
    {
        $document = LegalDocument::where('key', 'privacy_policy')->firstOrFail();
        $document->update(['lead' => 'Обновлённая аннотация политики конфиденциальности.']);

        $this->assertSame('Обновлённая аннотация политики конфиденциальности.', LegalDocuments::get('privacy_policy')?->lead);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Обновлённая аннотация политики конфиденциальности.');
    }

    public function test_documents_are_cached_as_plain_arrays_not_models(): void
    {
        LegalDocuments::flush();
        LegalDocuments::all();

        $cached = Cache::get('app.legal_documents.attributes');

        $this->assertIsArray($cached);
        $this->assertNotEmpty($cached);

        foreach ($cached as $row) {
            // Сериализация Eloquent-моделей приводила к __PHP_Incomplete_Class.
            $this->assertIsArray($row);
        }

        $this->assertContainsOnlyInstancesOf(LegalDocument::class, LegalDocuments::all());
    }

    public function test_manager_role_can_still_open_admin_panel(): void
    {
        $manager = User::where('email', 'manager@korni.local')->firstOrFail();

        $this->actingAs($manager)->get('/admin')->assertOk();

        $this->assertSame(UserRole::Manager, $manager->role);
    }
}
