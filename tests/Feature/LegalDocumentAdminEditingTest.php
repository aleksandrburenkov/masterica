<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalDocuments\Pages\EditLegalDocument;
use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
