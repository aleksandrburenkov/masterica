<?php

namespace App\Http\Controllers\Account;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CourseService;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly OrderService $orders,
        private readonly CartService $cart,
    ) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $upcoming = $user->bookings()
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::PendingPayment->value])
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>', now()))
            ->with('session.masterclass')
            ->get()
            ->sortBy(fn ($b) => $b->session?->starts_at)
            ->first();

        $activeEnrollment = $user->enrollments()
            ->where('status', 'active')
            ->with('course')
            ->first();

        $progress = $activeEnrollment ? $this->courses->progressPercent($activeEnrollment) : 0;

        $counts = [
            'bookings' => $user->bookings()->count(),
            'courses' => $user->enrollments()->count(),
            'orders' => $user->orders()->count(),
        ];

        $notifications = $user->appNotifications()->latest()->take(5)->get();

        return view('pages.account.dashboard', compact('user', 'upcoming', 'activeEnrollment', 'progress', 'counts', 'notifications'));
    }

    public function bookings(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with(['session.masterclass', 'session.master'])
            ->latest()
            ->get();

        return view('pages.account.bookings', compact('bookings'));
    }

    public function courses(Request $request): View
    {
        $enrollments = $request->user()->enrollments()
            ->with('course')
            ->latest()
            ->get()
            ->map(fn ($enrollment) => [
                'enrollment' => $enrollment,
                'progress' => $this->courses->progressPercent($enrollment),
            ]);

        return view('pages.account.courses', compact('enrollments'));
    }

    public function orders(Request $request): View
    {
        $orders = $request->user()->orders()->with('items')->latest()->get();

        return view('pages.account.orders', compact('orders'));
    }

    public function order(Request $request, Order $order): View
    {
        $this->authorize('view', $order);
        $order->load('items');

        return view('pages.account.order', compact('order'));
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $this->orders->cancel($order, 'Отмена пользователем', $request->user());

        return back()->with('success', 'Заказ отменён.');
    }

    public function profile(Request $request): View
    {
        return view('pages.account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('success', 'Профиль обновлён.');
    }
}
