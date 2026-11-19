НОВОЕ ОБРАЩЕНИЕ С САЙТА

{{ $data->type }} № {{ $data->number }}

@foreach ($data->fields as $field)
{{ $field['label'] }}: {{ $field['value'] }}
@endforeach

Открыть в административной панели:
{{ $data->adminUrl }}

Письмо отправлено автоматически при поступлении нового обращения.
