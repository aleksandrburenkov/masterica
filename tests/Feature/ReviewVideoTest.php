<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Enums\ReviewVideoStatus;
use App\Enums\UserRole;
use App\Enums\VideoScanStatus;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use App\Models\ReviewVideo;
use App\Models\User;
use App\Services\ReviewModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_authenticated_user_can_submit_a_text_review(): void
    {
        $user = $this->client();

        $response = $this->actingAs($user)->post(route('reviews.store'), [
            'type' => 'text',
            'rating' => 5,
            'body' => 'Отличное занятие, всё понравилось!',
            'consent_data' => '1',
            'consent_publish' => '1',
        ]);

        $response->assertRedirect(route('reviews.index'));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'type' => 'text',
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function test_guest_cannot_submit_a_review(): void
    {
        $this->post(route('reviews.store'), [
            'type' => 'text',
            'body' => 'Отзыв гостя без авторизации.',
            'consent_data' => '1',
            'consent_publish' => '1',
        ])->assertRedirect(route('login'));
    }

    public function test_a_video_review_requires_a_file_or_a_link(): void
    {
        $this->actingAs($this->client())
            ->postJson(route('reviews.store'), [
                'type' => 'video',
                'body' => 'Видеоотзыв без файла и ссылки.',
                'consent_data' => '1',
                'consent_publish' => '1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('video_file');
    }

    public function test_javascript_video_link_is_rejected(): void
    {
        $this->actingAs($this->client())
            ->postJson(route('reviews.store'), [
                'type' => 'video',
                'body' => 'Ссылка с недопустимой схемой.',
                'video_url' => 'javascript:alert(1)',
                'consent_data' => '1',
                'consent_publish' => '1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('video_url');
    }

    public function test_uploaded_video_is_scanned_and_awaits_moderation(): void
    {
        $user = $this->client();

        $response = $this->actingAs($user)->post(route('reviews.store'), [
            'type' => 'video',
            'body' => 'Записал небольшой видеоотзыв о занятии.',
            'consent_data' => '1',
            'consent_publish' => '1',
            'video_file' => $this->videoUpload('my-review.mp4'),
        ]);

        $response->assertRedirect(route('reviews.index'));

        $review = Review::query()->where('user_id', $user->id)->firstOrFail();
        $video = $review->video()->firstOrFail();

        // Файл проверен и ждёт решения модератора в каталоге ожидания.
        $this->assertSame(VideoScanStatus::Clean, $video->scan_status);
        $this->assertSame(ReviewVideoStatus::Pending, $video->status);
        $this->assertSame('my-review.mp4', $video->original_name);
        $this->assertNotNull($video->checksum);
        $this->assertStringStartsWith('reviews/incoming/', $video->path);
        Storage::disk('local')->assertExists($video->path);

        // До модерации видео не показывается в карточке и не является публичным.
        $this->assertFalse($video->isReady());
        $this->assertSame(ReviewStatus::Pending, $review->status);
    }

    public function test_approving_a_review_publishes_the_video(): void
    {
        $review = $this->reviewWithPendingVideo($this->videoHeader().str_repeat("\x00", 2048));
        $path = $review->video->path;

        app(ReviewModerationService::class)->approve($review, $this->staff());

        $review->refresh();
        $video = $review->video()->firstOrFail();

        $this->assertSame(ReviewStatus::Approved, $review->status);
        $this->assertNotNull($review->published_at);
        $this->assertSame(ReviewVideoStatus::Ready, $video->status);
        $this->assertTrue($video->isReady());
        $this->assertStringStartsWith('reviews/videos/', $video->path);
        Storage::disk('local')->assertExists($video->path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_rejecting_a_review_deletes_the_video_but_keeps_the_text(): void
    {
        $review = $this->reviewWithPendingVideo($this->videoHeader().str_repeat("\x00", 2048));
        $videoId = $review->video->id;
        $path = $review->video->path;

        app(ReviewModerationService::class)->reject($review, $this->staff(), 'Не подходит');

        $review->refresh();

        $this->assertSame(ReviewStatus::Rejected, $review->status);
        $this->assertSame('Видеоотзыв для проверки модерации.', $review->body);
        $this->assertDatabaseMissing('review_videos', ['id' => $videoId]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_moderation_actions_in_the_admin_panel_publish_and_delete_videos(): void
    {
        $this->actingAs($this->staff());

        $toApprove = $this->reviewWithPendingVideo($this->videoHeader().str_repeat("\x00", 512));
        Livewire::test(ListReviews::class)->callTableAction('approve', $toApprove);
        $this->assertSame(ReviewVideoStatus::Ready, $toApprove->refresh()->video->status);

        $toReject = $this->reviewWithPendingVideo($this->videoHeader().str_repeat("\x00", 512));
        $rejectPath = $toReject->video->path;
        Livewire::test(ListReviews::class)->callTableAction('reject', $toReject);
        $this->assertDatabaseMissing('review_videos', ['id' => $toReject->video->id]);
        Storage::disk('local')->assertMissing($rejectPath);
    }

    public function test_video_with_embedded_script_is_quarantined_and_not_published(): void
    {
        $user = $this->client();

        $payload = $this->videoHeader().'<?php system($_GET["cmd"]); ?>'.str_repeat("\x00", 64);

        $this->actingAs($user)->post(route('reviews.store'), [
            'type' => 'video',
            'body' => 'Видео с вредоносной полезной нагрузкой.',
            'consent_data' => '1',
            'consent_publish' => '1',
            'video_file' => UploadedFile::fake()->createWithContent('evil.mp4', $payload),
        ])->assertRedirect(route('reviews.index'));

        $video = Review::query()->where('user_id', $user->id)->firstOrFail()->video()->firstOrFail();

        $this->assertSame(VideoScanStatus::Infected, $video->scan_status);
        $this->assertSame(ReviewVideoStatus::Quarantined, $video->status);
        $this->assertFalse($video->isPlayable());
        $this->assertNull($video->streamUrl());
        $this->assertStringStartsWith('reviews/quarantine/', (string) $video->path);
    }

    public function test_short_ascii_bytes_in_binary_do_not_trigger_a_false_threat(): void
    {
        // Последовательности вида "<%" и "<?=" случайно встречаются в двоичных
        // данных видео и не должны приводить к блокировке нормального файла.
        $contents = $this->videoHeader()
            .str_repeat("\x00", 512).'<%<?='
            .random_bytes(4096);

        $this->actingAs($this->client())->post(route('reviews.store'), [
            'type' => 'video',
            'body' => 'Обычное видео, которое не должно блокироваться.',
            'consent_data' => '1',
            'consent_publish' => '1',
            'video_file' => UploadedFile::fake()->createWithContent('normal.mp4', $contents),
        ])->assertRedirect(route('reviews.index'));

        $video = ReviewVideo::query()->firstOrFail();

        $this->assertSame(VideoScanStatus::Clean, $video->scan_status);
    }

    public function test_file_without_video_signature_is_treated_as_suspicious(): void
    {
        $this->actingAs($this->client())->post(route('reviews.store'), [
            'type' => 'video',
            'body' => 'Совсем не видео, а подделка с расширением mp4.',
            'consent_data' => '1',
            'consent_publish' => '1',
            'video_file' => UploadedFile::fake()->createWithContent('fake.mp4', 'MZ'.str_repeat('A', 256)),
        ])->assertRedirect(route('reviews.index'));

        $video = ReviewVideo::query()->firstOrFail();

        $this->assertSame(VideoScanStatus::Suspicious, $video->scan_status);
        $this->assertFalse($video->isPlayable());
    }

    public function test_oversized_video_is_rejected(): void
    {
        config(['video.max_size_kb' => 1]);

        $this->actingAs($this->client())
            ->postJson(route('reviews.store'), [
                'type' => 'video',
                'body' => 'Слишком большой видеофайл для загрузки.',
                'consent_data' => '1',
                'consent_publish' => '1',
                'video_file' => UploadedFile::fake()->createWithContent('big.mp4', $this->videoHeader().str_repeat("\x00", 4096)),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('video_file');

        $this->assertDatabaseCount('review_videos', 0);
    }

    public function test_published_video_is_streamed_publicly_with_range_support(): void
    {
        $video = $this->makeVideo(ReviewStatus::Approved, ReviewVideoStatus::Ready, $this->videoHeader().str_repeat('v', 128));

        $this->get(route('reviews.video', $video->review_id))
            ->assertOk()
            ->assertHeader('content-type', $video->mime_type)
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('accept-ranges', 'bytes');

        $partial = $this->get(route('reviews.video', $video->review_id), ['Range' => 'bytes=0-1']);

        $partial->assertStatus(206);
        $this->assertStringContainsString('bytes', (string) $partial->headers->get('Content-Range'));
    }

    public function test_unpublished_video_is_private(): void
    {
        $video = $this->makeVideo(ReviewStatus::Pending, ReviewVideoStatus::Pending, $this->videoHeader().str_repeat('v', 64));

        $this->get(route('reviews.video', $video->review_id))->assertForbidden();

        $this->actingAs($video->review->user)->get(route('reviews.video', $video->review_id))->assertOk();
        $this->actingAs($this->staff())->get(route('reviews.video', $video->review_id))->assertOk();
    }

    public function test_quarantined_video_is_never_served(): void
    {
        $video = $this->makeVideo(ReviewStatus::Approved, ReviewVideoStatus::Ready, 'x');
        $video->forceFill([
            'scan_status' => VideoScanStatus::Infected,
            'status' => ReviewVideoStatus::Quarantined,
        ])->save();

        $this->get(route('reviews.video', $video->review_id))->assertNotFound();
    }

    public function test_deleting_a_review_removes_its_video_files(): void
    {
        $video = $this->makeVideo(ReviewStatus::Pending, ReviewVideoStatus::Pending, $this->videoHeader().str_repeat('v', 64));

        Storage::disk('local')->assertExists($video->path);

        $video->review->delete();

        Storage::disk('local')->assertMissing($video->path);
        $this->assertDatabaseMissing('review_videos', ['id' => $video->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $video->review_id]);
    }

    public function test_rescan_command_restores_a_false_positive_to_awaiting_moderation(): void
    {
        $video = $this->makeVideo(ReviewStatus::Pending, ReviewVideoStatus::Pending, $this->videoHeader().str_repeat("\x00", 64));

        $quarantine = 'reviews/quarantine/'.$video->id.'.mp4';
        Storage::disk('local')->move($video->path, $quarantine);
        $video->forceFill([
            'path' => $quarantine,
            'status' => ReviewVideoStatus::Quarantined,
            'scan_status' => VideoScanStatus::Suspicious,
        ])->save();

        $this->artisan('videos:process', ['--rescan-quarantine' => true])->assertSuccessful();

        $video->refresh();

        $this->assertSame(VideoScanStatus::Clean, $video->scan_status);
        $this->assertSame(ReviewVideoStatus::Pending, $video->status);
        $this->assertStringStartsWith('reviews/incoming/', $video->path);
        Storage::disk('local')->assertExists($video->path);
    }

    public function test_review_text_with_html_is_stored_and_escaped_not_executed(): void
    {
        $payload = '<script>alert(1)</script> и немного текста';

        $review = Review::create([
            'user_id' => $this->client()->id,
            'type' => 'text',
            'body' => $payload,
            'status' => ReviewStatus::Approved,
            'published_at' => now(),
            'consent_data' => true,
            'consent_publish' => true,
        ]);

        // Текст сохраняется как есть (пользователь может писать угловые скобки)…
        $this->assertSame($payload, $review->fresh()->body);

        // …но на публичной странице выводится экранированным и не исполняется.
        $public = $this->get(route('reviews.index'));
        $public->assertOk();
        $public->assertDontSee($payload, false);
        $public->assertSee($payload);

        // …и в админке тоже показан как обычный текст (экранированный).
        $this->actingAs($this->staff());
        Livewire::test(ListReviews::class)
            ->assertDontSee($payload, false)
            ->assertSee($payload);
    }

    public function test_admin_video_info_view_renders_metadata(): void
    {
        $video = $this->makeVideo(ReviewStatus::Pending, ReviewVideoStatus::Pending, $this->videoHeader().str_repeat('v', 64));

        $html = view('filament.review-video-info', ['video' => $video])->render();

        $this->assertStringContainsString('Чисто', $html);
        $this->assertStringContainsString('video/mp4', $html);
        $this->assertStringContainsString(route('reviews.video', $video->review_id), $html);
    }

    public function test_review_edit_page_renders_with_an_uploaded_video(): void
    {
        $video = $this->makeVideo(ReviewStatus::Pending, ReviewVideoStatus::Pending, $this->videoHeader().str_repeat('v', 64));

        $this->actingAs($this->staff());

        Livewire::test(EditReview::class, ['record' => $video->review_id])
            ->assertSuccessful();
    }

    private function client(): User
    {
        return User::factory()->create(['role' => UserRole::Client->value]);
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => UserRole::Manager->value]);
    }

    /** Минимальный заголовок MP4-контейнера, проходящий проверку сигнатуры. */
    private function videoHeader(): string
    {
        return "\x00\x00\x00\x18ftypisom\x00\x00\x02\x00isomiso2mp41";
    }

    private function videoUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            $this->videoHeader().str_repeat("\x00", 2048),
        );
    }

    /** Создаёт отзыв с ожидающим модерации видео (файл в каталоге ожидания). */
    private function reviewWithPendingVideo(string $contents): Review
    {
        $user = $this->client();

        $review = Review::create([
            'user_id' => $user->id,
            'type' => 'video',
            'body' => 'Видеоотзыв для проверки модерации.',
            'status' => ReviewStatus::Pending,
            'consent_data' => true,
            'consent_publish' => true,
        ]);

        $path = 'reviews/incoming/test-'.$review->id.'.mp4';
        Storage::disk('local')->put($path, $contents);

        $review->video()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'test.mp4',
            'extension' => 'mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'status' => ReviewVideoStatus::Pending,
            'scan_status' => VideoScanStatus::Clean,
            'scanner' => 'heuristic',
            'scanned_at' => now(),
        ]);

        return $review->fresh();
    }

    private function makeVideo(ReviewStatus $status, ReviewVideoStatus $videoStatus, string $contents): ReviewVideo
    {
        $review = $this->reviewWithPendingVideo($contents);
        $review->forceFill([
            'status' => $status,
            'published_at' => $status === ReviewStatus::Approved ? now() : null,
        ])->save();

        $video = $review->video()->firstOrFail();
        $video->forceFill(['status' => $videoStatus])->save();

        return $video->refresh();
    }
}
