@extends('layouts.site')

@section('title', 'Оформление заказа — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Магазин', 'url' => route('shop.index')], ['label' => 'Оформление заказа']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <h1>Оформление заказа</h1>
            <p class="lead-muted">Контакт взрослого получателя. Итоговая сумма пересчитывается на сервере.</p>

            <div class="cart-layout" style="margin-top:24px">
                <form class="card" method="post" action="{{ route('checkout.store') }}" data-ajax-form>
                    @csrf
                    <h3>Контактные данные</h3>
                    <div class="field">
                        <label for="c-name">Имя</label>
                        <input id="c-name" class="input @error('contact_name') is-invalid @enderror" type="text" name="contact_name" value="{{ old('contact_name', auth()->user()->name) }}" required>
                    </div>
                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="c-phone">Телефон</label>
                            <input id="c-phone" class="input" type="text" name="contact_phone" value="{{ old('contact_phone', auth()->user()->phone) }}" required>
                        </div>
                        <div class="field">
                            <label for="c-email">Электронная почта</label>
                            <input id="c-email" class="input" type="email" name="contact_email" value="{{ old('contact_email', auth()->user()->email) }}" required>
                        </div>
                    </div>

                    <h3 style="margin-top:10px">Получение</h3>
                    @foreach ($deliveryOptions as $option)
                        <label class="checkbox" style="margin-bottom:10px">
                            <input type="radio" name="delivery_method" value="{{ $option['value'] }}" @checked(old('delivery_method', 'pickup') === $option['value']) required>
                            <span>{{ $option['label'] }} — {{ $option['price_cents'] > 0 ? money($option['price_cents']) : 'бесплатно' }}</span>
                        </label>
                    @endforeach

                    <div class="field">
                        <label for="c-address">Адрес доставки (для доставки по городу)</label>
                        <input id="c-address" class="input" type="text" name="address" value="{{ old('address') }}" placeholder="Улица, дом, квартира">
                    </div>

                    <div class="field">
                        <label for="c-comment">Комментарий</label>
                        <textarea id="c-comment" class="textarea" name="comment" placeholder="Необязательно">{{ old('comment') }}</textarea>
                    </div>

                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="accept_offer" value="1" required>
                            <span>
                                Я ознакомлен(а) и согласен(на) с
                                <a href="{{ route('legal.show', legal('offer_terms', 'slug', 'publichnaya-oferta')) }}" target="_blank" rel="noopener">условиями Публичной оферты</a>
                                и принимаю их (акцепт оферты). Условия возврата и обмена товара указаны в оферте.
                            </span>
                        </label>
                        @error('accept_offer')<div class="error-text">{{ $message }}</div>@enderror
                        <label class="checkbox" style="margin-top:8px">
                            <input type="checkbox" name="consent_data" value="1" required>
                            <span>
                                {{ legal('consent_checkout', 'lead', 'Даю согласие на обработку персональных данных для оформления и доставки заказа') }}.
                                <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}" target="_blank" rel="noopener">Условия обработки</a>
                            </span>
                        </label>
                        @error('consent_data')<div class="error-text">{{ $message }}</div>@enderror
                        <label class="checkbox" style="margin-top:8px">
                            <input type="checkbox" name="consent_marketing" value="1">
                            <span>{{ legal('consent_marketing', 'lead', 'Согласен(на) получать новости и предложения студии (необязательно)') }}</span>
                        </label>
                    </div>

                    <button class="btn btn--block" type="submit">Подтвердить заказ</button>
                    <p class="form-note" style="margin-top:14px">{{ $paymentNote }} Срок подготовки: {{ $prepDays }} рабочих дней.</p>
                </form>

                <div class="summary">
                    <h3>Ваш заказ</h3>
                    @foreach ($items as $item)
                        <div class="summary__row">
                            <span>{{ $item['product']->title }} <span class="muted">× {{ $item['quantity'] }}</span></span>
                            <span>{{ money($item['line_total_cents']) }}</span>
                        </div>
                    @endforeach
                    <div class="summary__row"><span>Товары</span><span>{{ money($subtotal) }}</span></div>
                    <div class="summary__row total"><span>К оплате</span><span>{{ money($subtotal) }} + доставка</span></div>
                    <p class="form-note" style="margin-top:14px">Оплата при получении — платёжный провайдер не подключён.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
