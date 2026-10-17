<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Master;
use App\Models\Masterclass;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkshopSession;
use App\Services\StockService;
use App\Support\NumberGenerator;
use Illuminate\Database\Seeder;

/**
 * Local-only demonstration data. Never load this seeder in production:
 * all reviews and profiles created here are synthetic and flagged as demo.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->seedStaff();
        $client = $this->seedDemoClient();
        $this->seedBooking($client);
        $this->seedOrder($client);
        $this->seedEnrollment($client);
        $this->seedReviews($owner);
    }

    private function seedStaff(): User
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@korni.local'],
            [
                'name' => 'Ирина Буренкова',
                'password' => 'password',
                'role' => UserRole::Owner,
                'phone' => '+7 (906) 698-31-06',
                'email_verified_at' => now(),
            ],
        );

        Master::query()->where('name', 'Ирина Буренкова')->update(['user_id' => $owner->id]);

        User::query()->updateOrCreate(
            ['email' => 'manager@korni.local'],
            [
                'name' => 'Менеджер студии',
                'password' => 'password',
                'role' => UserRole::Manager,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'master@korni.local'],
            [
                'name' => 'Мастер студии',
                'password' => 'password',
                'role' => UserRole::Master,
                'email_verified_at' => now(),
            ],
        );

        return $owner;
    }

    private function seedDemoClient(): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'demo@korni.local'],
            [
                'name' => 'Демо Клиент',
                'password' => 'password',
                'role' => UserRole::Client,
                'phone' => '+7 (900) 000-00-00',
                'email_verified_at' => now(),
            ],
        );
    }

    private function seedBooking(User $client): void
    {
        if ($client->bookings()->exists()) {
            return;
        }

        $session = WorkshopSession::query()
            ->where('is_published', true)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->first();

        if (! $session) {
            return;
        }

        $unit = $session->priceCents();
        $participants = 2;

        Booking::create([
            'number' => NumberGenerator::booking(),
            'user_id' => $client->id,
            'workshop_session_id' => $session->id,
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
            'participants_count' => $participants,
            'contact_name' => $client->name,
            'contact_phone' => $client->phone,
            'contact_email' => $client->email,
            'unit_price_cents' => $unit,
            'total_cents' => $unit * $participants,
            'currency' => 'RUB',
            'confirmed_at' => now(),
            'notes' => 'Демонстрационная запись (seed).',
        ]);
    }

    private function seedOrder(User $client): void
    {
        if ($client->orders()->exists()) {
            return;
        }

        $variant = Product::query()->where('slug', 'basket-paper-vine')->first()?->variants()->first();
        if (! $variant) {
            return;
        }

        $quantity = 1;
        $lineTotal = $variant->price_cents * $quantity;

        $order = Order::create([
            'number' => NumberGenerator::order(),
            'user_id' => $client->id,
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'delivery_method' => 'pickup',
            'subtotal_cents' => $lineTotal,
            'delivery_cents' => 0,
            'total_cents' => $lineTotal,
            'currency' => 'RUB',
            'contact_name' => $client->name,
            'contact_phone' => $client->phone,
            'contact_email' => $client->email,
            'placed_at' => now()->subDays(2),
            'paid_at' => now()->subDays(2),
        ]);

        $order->items()->create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => $variant->product->title,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'unit_price_cents' => $variant->price_cents,
            'quantity' => $quantity,
            'line_total_cents' => $lineTotal,
            'currency' => 'RUB',
        ]);

        app(StockService::class)->record(
            $variant,
            StockMovementType::Reserve,
            -1 * $quantity,
            'Резерв по демонстрационному заказу '.$order->number,
            $client,
            $order,
        );
    }

    private function seedEnrollment(User $client): void
    {
        $course = Course::query()->where('slug', 'paper-vine-basket-course')->first();
        if (! $course) {
            return;
        }

        CourseEnrollment::query()->updateOrCreate(
            ['user_id' => $client->id, 'course_id' => $course->id],
            [
                'status' => EnrollmentStatus::Active,
                'payment_status' => PaymentStatus::Paid,
                'granted_at' => now()->subDays(3),
                'expires_at' => now()->addDays($course->access_days),
            ],
        );
    }

    private function seedReviews(User $owner): void
    {
        if (Review::query()->where('is_demo', true)->exists()) {
            return;
        }

        $rows = [
            ['Анна С.', 'Брянск', 5, 'Ходили с дочкой на плетение из бумажной лозы. Мастер всё объяснила по шагам, ребёнку было интересно, унесли домой готовую корзину.'],
            ['Мария К.', 'Брянск', 5, 'Очень тёплая атмосфера в студии. Плели впервые, результат порадовал. Обязательно придём ещё.'],
            ['Ольга В.', 'Брянск', 4, 'Понравился мастер-класс по канзаши. Всё понятно и аккуратно, материалов хватило.'],
            ['Дмитрий П.', 'Брянск', 5, 'Заказывали выездной мастер-класс на праздник для детей. Дети были в восторге, организация на высоте.'],
            ['Екатерина Н.', 'Брянск', 5, 'Купила курс, занимаюсь дома по видеоурокам. Программа понятная, доступ к урокам удобный.'],
        ];

        $masterclasses = Masterclass::query()->published()->get();

        foreach ($rows as $i => [$name, $city, $rating, $body]) {
            $author = User::query()->updateOrCreate(
                ['email' => 'demo-reviewer'.$i.'@korni.local'],
                [
                    'name' => $name,
                    'password' => 'password',
                    'role' => UserRole::Client,
                    'email_verified_at' => now(),
                ],
            );

            $review = Review::create([
                'user_id' => $author->id,
                'masterclass_id' => $masterclasses->isNotEmpty() ? $masterclasses[$i % $masterclasses->count()]->id : null,
                'type' => ReviewType::Text,
                'rating' => $rating,
                'body' => $body,
                'city' => $city,
                'is_demo' => true,
                'status' => ReviewStatus::Approved,
                'consent_publish' => true,
                'consent_marketing' => (bool) ($i % 2 === 0),
                'source' => 'Синтетический демонстрационный отзыв',
                'published_at' => now()->subDays(5 - $i),
                'moderated_by' => $owner->id,
                'moderated_at' => now()->subDays(4),
            ]);

            $review->forceFill(['is_demo' => true])->save();
        }
    }
}
