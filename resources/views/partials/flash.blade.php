@if (session('success'))
    <div class="flash flash--success" role="status">{{ session('success') }}</div>
@endif
@if (session('info'))
    <div class="flash flash--info" role="status">{{ session('info') }}</div>
@endif
@if (session('error'))
    <div class="flash flash--error" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="flash flash--error" role="alert">
        <strong>Проверьте заполнение формы:</strong>
        <ul style="margin:8px 0 0;padding-left:18px">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
