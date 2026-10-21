<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Арт-студия «{{ setting('studio.name', 'Корни') }}»">
                <p>Творческие занятия и авторские работы в {{ setting('studio.city', 'Брянске') }}.</p>
            </div>

            <div>
                <h5>Связаться</h5>
                <a class="footer-phone"
                    href="tel:{{ preg_replace('/[^0-9+]/', '', setting('studio.phone', '')) }}">{{ setting('studio.phone', '+7 (906) 698-31-06') }}</a>
                <ul>
                    <li>{{ setting('studio.address', 'г. Брянск, ул. Московская, д. 23') }}</li>
                    <li>{{ setting('studio.hours', 'Ежедневно 10:00–19:00') }}</li>
                    @if (setting('studio.email'))
                        <li><a href="mailto:{{ setting('studio.email') }}">{{ setting('studio.email') }}</a></li>
                    @endif
                </ul>
            </div>

            <div>
                <h5>Разделы</h5>
                <ul>
                    <li><a href="{{ route('about') }}">О студии</a></li>
                    <li><a href="{{ route('masterclasses.index') }}">Мастер-классы</a></li>
                    <li><a href="{{ route('shop.index') }}">Магазин работ</a></li>
                    <li><a href="{{ route('courses.index') }}">Онлайн-курсы</a></li>
                    <li><a href="{{ route('reviews.index') }}">Отзывы</a></li>
                    <li><a href="{{ route('contacts') }}">Контакты</a></li>
                </ul>
            </div>

            <div>
                <h5>Правовая информация</h5>
                @php($footerDocs = collect($legalDocuments ?? []))
                @if ($footerDocs->isNotEmpty())
                    <ul>
                        @foreach ($footerDocs as $doc)
                            <li><a href="{{ route('legal.show', $doc->slug) }}">{{ $doc->title }}</a></li>
                        @endforeach
                    </ul>
                @endif
                @php($privacy = $footerDocs->get('privacy_policy'))
                @if ($privacy)
                    <p class="footer-legal-note" style="font-size:13px;margin-top:10px;opacity:.85">
                        Персональные данные обрабатываются в соответствии с Федеральным законом № 152-ФЗ «О персональных данных».
                        <a href="{{ route('legal.show', $privacy->slug) }}">Подробнее</a>.
                    </p>
                @endif
            </div>
        </div>

        <div class="footer-bottom">
            <span>© {{ date('Y') }} Арт-студия «{{ setting('studio.name', 'Корни') }}»</span>
            <span>{{ setting('studio.legal_note') }}</span>
        </div>
    </div>
</footer>
