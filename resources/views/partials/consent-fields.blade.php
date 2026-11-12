{{--
    Единый блок чекбоксов согласия для всех форм сайта.

    Все строки выводятся с одинаковой разметкой и оформлением (класс .checkbox),
    тексты берутся из существующих правовых документов:
      — Политика конфиденциальности  (privacy_policy);
      — Пользовательское соглашение  (public_offer / platform_terms);
      — Оплата и возврат             (personal_data).

    Параметры:
      $dataTextKey     — ключ документа-согласия на обработку ПД (текст чекбокса);
      $dataFallback    — текст согласия по умолчанию (если документа нет);
      $dataText        — явный текст согласия (когда отдельного документа нет);
      $termsKey        — ключ «Пользовательского соглашения» (public_offer по умолчанию);
      $termsSlug       — резервный слаг «Пользовательского соглашения»;
      $withPayment     — добавить в подтверждение ссылку «Оплата и возврат»;
      $acceptOffer     — отдельный чекбокс акцепта оферты (только оформление заказа);
      $publishFallback — текст согласия на публикацию отзыва (только форма отзыва);
      $withMarketing   — показывать необязательное согласие на рассылку (по умолчанию true);
      $firstMargin     — верхний отступ первого чекбокса (по умолчанию «0»).
--}}
@php
    $dataTextKey = $dataTextKey ?? null;
    $dataText = $dataText ?? ($dataFallback ?? '');
    $termsKey = $termsKey ?? 'public_offer';
    $termsSlug = $termsSlug ?? 'offer';
    $withPayment = $withPayment ?? false;
    $acceptOffer = $acceptOffer ?? false;
    $publishFallback = $publishFallback ?? null;
    $withMarketing = $withMarketing ?? true;
    $margin = $firstMargin ?? '0';

    $privacyUrl = route('legal.show', legal('privacy_policy', 'slug', 'politika-konfidencialnosti'));
    $termsUrl = route('legal.show', legal($termsKey, 'slug', $termsSlug));
    $paymentUrl = route('legal.show', legal('personal_data', 'slug', 'personal'));
@endphp

@if ($acceptOffer)
    <label class="checkbox" style="margin-top:{{ $margin }}">
        <input type="checkbox" name="accept_offer" value="1" required>
        <span>
            Я ознакомлен(а) и согласен(на) с условиями
            <a href="{{ $paymentUrl }}" target="_blank" rel="noopener">Оплаты и возврата</a>
            и принимаю их (акцепт оферты).
        </span>
    </label>
    @error('accept_offer')<div class="error-text">{{ $message }}</div>@enderror
    @php $margin = '8px'; @endphp
@endif

<label class="checkbox" style="margin-top:{{ $margin }}">
    <input type="checkbox" name="consent_data" value="1" required>
    <span>
        {{ $dataTextKey ? legal($dataTextKey, 'lead', $dataText) : $dataText }}.
        Я ознакомлен(а) и согласен(на) с
        <a href="{{ $privacyUrl }}" target="_blank" rel="noopener">Политикой конфиденциальности</a>
        и <a href="{{ $termsUrl }}" target="_blank" rel="noopener">Пользовательским соглашением</a>@if ($withPayment), а также с условиями <a href="{{ $paymentUrl }}" target="_blank" rel="noopener">Оплаты и возврата</a>@endif.
    </span>
</label>
@error('consent_data')<div class="error-text">{{ $message }}</div>@enderror

@if ($publishFallback !== null)
    <label class="checkbox" style="margin-top:8px">
        <input type="checkbox" name="consent_publish" value="1" required>
        <span>
            {{ legal('consent_review', 'lead', $publishFallback) }}.
            <a href="{{ route('legal.show', legal('review_consent', 'slug', 'soglasie-na-publikaciyu-otzyva')) }}" target="_blank" rel="noopener">Подробнее</a>
        </span>
    </label>
@endif

@if ($withMarketing)
    <label class="checkbox" style="margin-top:8px">
        <input type="checkbox" name="consent_marketing" value="1">
        <span>{{ legal('consent_marketing', 'lead', 'Даю согласие на получение информационных и рекламных сообщений студии (необязательно); согласие можно отозвать в любое время') }}.</span>
    </label>
@endif
