<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OutreachStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Filament\Resources\ContactRequests\Pages\EditContactRequest;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\OutreachRequests\OutreachRequestResource;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Support\NavigationGroupIndicator;
use App\Models\ContactRequest;
use App\Models\Order;
use App\Models\OutreachRequest;
use App\Models\Review;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNavigationBadgesTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => UserRole::Owner]);
    }

    private function contactRequest(array $attributes = []): ContactRequest
    {
        return ContactRequest::create(array_merge([
            'number' => 'CT-'.uniqid(),
            'contact_name' => 'Тест',
            'contact_phone' => '+79000000000',
            'contact_email' => 'test@example.com',
            'message' => 'Сообщение',
            'status' => OutreachStatus::New,
        ], $attributes));
    }

    private function outreachRequest(array $attributes = []): OutreachRequest
    {
        return OutreachRequest::create(array_merge([
            'number' => 'OR-'.uniqid(),
            'direction' => 'Выездной мастер-класс',
            'requested_date' => now()->addWeek()->toDateString(),
            'location' => 'Брянск',
            'participants_count' => 8,
            'contact_name' => 'Тест',
            'contact_phone' => '+79000000000',
            'contact_email' => 'test@example.com',
            'status' => OutreachStatus::New,
        ], $attributes));
    }

    private function review(array $attributes = []): Review
    {
        return Review::create(array_merge([
            'user_id' => $this->user()->id,
            'body' => 'Отзыв',
            'status' => ReviewStatus::Pending,
        ], $attributes));
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'number' => 'ORD-'.uniqid(),
            'user_id' => $this->user()->id,
            'status' => OrderStatus::PendingPayment,
            'contact_name' => 'Тест',
            'contact_phone' => '+79000000000',
            'contact_email' => 'test@example.com',
        ], $attributes));
    }

    public function test_contact_request_badge_counts_only_new_records(): void
    {
        $this->assertNull(ContactRequestResource::getNavigationBadge());

        $this->contactRequest();
        $this->contactRequest();
        $this->contactRequest(['status' => OutreachStatus::Contacted]);

        $this->assertSame('2', ContactRequestResource::getNavigationBadge());
    }

    public function test_badge_disappears_when_record_is_processed(): void
    {
        $request = $this->contactRequest();

        $this->assertSame('1', ContactRequestResource::getNavigationBadge());

        $request->update(['status' => OutreachStatus::Contacted]);

        $this->assertNull(ContactRequestResource::getNavigationBadge());
    }

    public function test_outreach_request_badge_counts_new_records(): void
    {
        $this->assertNull(OutreachRequestResource::getNavigationBadge());

        $this->outreachRequest();

        $this->assertSame('1', OutreachRequestResource::getNavigationBadge());
    }

    public function test_review_badge_counts_pending_moderation(): void
    {
        $this->assertNull(ReviewResource::getNavigationBadge());

        $this->review();

        $this->assertSame('1', ReviewResource::getNavigationBadge());
    }

    public function test_order_badge_counts_orders_awaiting_processing(): void
    {
        $this->assertNull(OrderResource::getNavigationBadge());

        $this->order();

        $this->assertSame('1', OrderResource::getNavigationBadge());

        $this->order(['status' => OrderStatus::Completed]);

        $this->assertSame('1', OrderResource::getNavigationBadge());
    }

    public function test_group_indicator_sums_unread_records_of_its_resources(): void
    {
        $this->assertSame([], NavigationGroupIndicator::attributes([OutreachRequestResource::class]));

        $this->outreachRequest();
        $this->outreachRequest();

        $attributes = NavigationGroupIndicator::attributes([OutreachRequestResource::class]);

        $this->assertSame('fi-nav-group-has-new', $attributes['class']);
        $this->assertSame('2', $attributes['data-nav-new-count']);
    }

    public function test_registered_group_marker_follows_unread_records(): void
    {
        $group = collect(Filament::getPanel('admin')->getNavigationGroups())
            ->first(fn ($group): bool => $group instanceof NavigationGroup && $group->getLabel() === 'Заявки');

        $this->assertInstanceOf(NavigationGroup::class, $group);
        $this->assertSame([], $group->getExtraSidebarAttributes());

        $this->outreachRequest();

        $attributes = $group->getExtraSidebarAttributes();

        $this->assertSame('fi-nav-group-has-new', $attributes['class']);
        $this->assertSame('1', $attributes['data-nav-new-count']);
    }

    public function test_saving_a_status_change_dispatches_sidebar_refresh(): void
    {
        $this->actingAs($this->user());

        $request = $this->contactRequest();

        $this->assertSame('1', ContactRequestResource::getNavigationBadge());

        Livewire::test(EditContactRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm(['status' => OutreachStatus::Contacted->value])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertDispatched('refresh-sidebar');

        $this->assertNull(ContactRequestResource::getNavigationBadge());
    }

    public function test_moderating_a_review_dispatches_sidebar_refresh(): void
    {
        $this->actingAs($this->user());

        $review = $this->review();

        $this->assertSame('1', ReviewResource::getNavigationBadge());

        Livewire::test(ListReviews::class)
            ->callTableAction('approve', $review)
            ->assertDispatched('refresh-sidebar');

        $this->assertNull(ReviewResource::getNavigationBadge());
    }
}
