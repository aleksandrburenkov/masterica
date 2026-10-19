<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterclassController;
use App\Http\Controllers\OutreachController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Публичные маршруты
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// О студии и информационные страницы
Route::get('/o-studii', [PageController::class, 'about'])->name('about');
Route::get('/kak-prohodyat-zanyatiya', [PageController::class, 'howClassesWork'])->name('how-classes-work');
Route::get('/kontakty', [PageController::class, 'contacts'])->name('contacts');
Route::get('/master-klassy-v-bryanske', [MasterclassController::class, 'bryansk'])->name('masterclasses.bryansk');

// Мастер-классы и запись
Route::get('/master-klassy', [MasterclassController::class, 'index'])->name('masterclasses.index');
Route::get('/master-klassy/{masterclass:slug}', [MasterclassController::class, 'show'])->name('masterclasses.show');

// Запись на мастер-класс доступна без регистрации и входа.
Route::post('/master-klassy/{masterclass:slug}/zapis', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')->name('bookings.store');

// Выездные мастер-классы
Route::get('/vyezdnye-master-klassy', [OutreachController::class, 'index'])->name('outreach.index');
Route::post('/vyezdnye-master-klassy', [OutreachController::class, 'store'])
    ->middleware('throttle:10,1')->name('outreach.store');

// Магазин
Route::get('/magazin', [ShopController::class, 'index'])->name('shop.index');
Route::get('/magazin/{product:slug}', [ShopController::class, 'show'])->name('shop.show');

// Корзина
Route::get('/korzina', [CartController::class, 'index'])->name('cart.index');
Route::post('/korzina', [CartController::class, 'add'])->name('cart.add');
Route::patch('/korzina/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/korzina/{variant}', [CartController::class, 'remove'])->name('cart.remove');

// Онлайн-курсы
Route::get('/kursy', [CourseController::class, 'index'])->name('courses.index');
Route::get('/kursy/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

// Отзывы
Route::get('/otzyvy', [ReviewController::class, 'index'])->name('reviews.index');

// Материалы
Route::get('/materialy', [PageController::class, 'guides'])->name('guides.index');
Route::get('/materialy/{page:slug}', [PageController::class, 'guide'])->name('guides.show');

// Поиск
Route::get('/poisk', [SearchController::class, 'index'])->name('search');

// Правовые документы (политика, согласия, оферта, лицензия и др.)
Route::get('/politika-konfidencialnosti', [PageController::class, 'privacy'])->name('legal.privacy');
Route::get('/soglasie-na-obrabotku-dannyh', [PageController::class, 'consent'])->name('legal.consent');
Route::get('/pravila-zapisi-i-zakaza', [PageController::class, 'rules'])->name('legal.rules');
Route::get('/dokumenty/{document:slug}', [PageController::class, 'legal'])->name('legal.show');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');

/*
|--------------------------------------------------------------------------
| Аутентификация
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/vhod', [LoginController::class, 'show'])->name('login');
    Route::post('/vhod', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/registraciya', [RegisterController::class, 'show'])->name('register');
    Route::post('/registraciya', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');
});
Route::post('/vyhod', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Сценарии, требующие аккаунта
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Отмена записи доступна только владельцу записи в личном кабинете.
    Route::post('/zapisi/{booking:number}/otmena', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // Оформление заказа
    Route::middleware('no-store')->group(function () {
        Route::get('/oformlenie', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/oformlenie', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/oformlenie/uspeh/{order:number}', [CheckoutController::class, 'success'])->name('checkout.success');
    });

    // Запись на курс и уроки
    Route::post('/kursy/{course:slug}/zapis', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::middleware('no-store')->group(function () {
        Route::get('/kursy/{course:slug}/urok/{lesson}', [CourseController::class, 'learn'])->name('courses.learn');
        Route::post('/kursy/{course:slug}/urok/{lesson}/gotovo', [CourseController::class, 'complete'])->name('courses.complete');
    });

    // Отзывы
    Route::post('/otzyvy', [ReviewController::class, 'store'])->middleware('throttle:5,1')->name('reviews.store');

    // Личный кабинет
    Route::prefix('kabinet')->name('account.')->middleware('no-store')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/zapisi', [AccountController::class, 'bookings'])->name('bookings');
        Route::get('/kursy', [AccountController::class, 'courses'])->name('courses');
        Route::get('/zakazy', [AccountController::class, 'orders'])->name('orders');
        Route::get('/zakazy/{order:number}', [AccountController::class, 'order'])->name('order');
        Route::post('/zakazy/{order:number}/otmena', [AccountController::class, 'cancelOrder'])->name('order.cancel');
        Route::get('/profil', [AccountController::class, 'profile'])->name('profile');
        Route::patch('/profil', [AccountController::class, 'updateProfile'])->name('profile.update');
    });
});
