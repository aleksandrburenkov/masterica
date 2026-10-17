<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Арт-студия «{{ setting('studio.name', 'Корни') }}»">
                <p>Творческие занятия и авторские работы в {{ setting('studio.city', 'Брянске') }}.</p>
            </div>

            <div>
                <h5>Связаться</h5>
                <a class="footer-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', setting('studio.phone', '')) }}">{{ setting('studio.phone', '+7 (906) 698-31-06') }}</a>
                <ul>
                    <li>{{ setting('studio.address', 'г. Брянск, ул. Московская, д. 23') }}</li>
                    <li>{{ setting('studio.hours', 'Ежедневно 10:00–19:00') }}</li>
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
                <h5>Важно</h5>
                <ul>
                    <li><a href="{{ route('legal.privacy') }}">Политика конфиденциальности</a></li>
                    <li><a href="{{ route('legal.consent') }}">Согласие на обработку данных</a></li>
                    <li><a href="{{ route('legal.rules') }}">Правила записи и заказа</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© {{ date('Y') }} Арт-студия «{{ setting('studio.name', 'Корни') }}»</span>
            <span>{{ setting('studio.legal_note') }}</span>
        </div>
    </div>
</footer>
