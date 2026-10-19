@php
    $flashMessages = [];

    foreach (['success', 'info', 'error'] as $type) {
        if (session($type)) {
            $flashMessages[] = ['type' => $type, 'text' => session($type)];
        }
    }
@endphp

@if ($flashMessages !== [] || $errors->any())
    <div class="flash-stack" data-flash-stack aria-live="polite">
        @foreach ($flashMessages as $message)
            <div class="flash flash--{{ $message['type'] }} flash-toast" data-flash-toast role="{{ $message['type'] === 'error' ? 'alert' : 'status' }}">
                <div class="flash-toast__text">{{ $message['text'] }}</div>
                <button type="button" class="flash-toast__close" data-flash-close aria-label="Закрыть уведомление">&times;</button>
            </div>
        @endforeach

        @if ($errors->any())
            <div class="flash flash--error flash-toast" data-flash-toast role="alert">
                <div class="flash-toast__text">
                    <strong>Проверьте заполнение формы:</strong>
                    <ul class="flash-toast__list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="flash-toast__close" data-flash-close aria-label="Закрыть уведомление">&times;</button>
            </div>
        @endif
    </div>
@endif
