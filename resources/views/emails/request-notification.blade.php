<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data->subject() }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f1ec;font-family:Arial,Helvetica,sans-serif;color:#2b2622;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f1ec;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background-color:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5ded4;">
                <tr>
                    <td style="background-color:#4c3b8f;padding:24px 28px;color:#ffffff;">
                        <div style="font-size:13px;letter-spacing:1.5px;text-transform:uppercase;opacity:.8;">Арт-студия «Корни»</div>
                        <h1 style="margin:8px 0 0;font-size:24px;line-height:1.3;">Новое обращение с сайта</h1>
                        <div style="margin-top:8px;font-size:15px;opacity:.9;">{{ $data->type }} № {{ $data->number }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:26px 28px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                            @foreach ($data->fields as $field)
                                <tr>
                                    <td style="padding:10px 12px;border-bottom:1px solid #eee8df;width:40%;vertical-align:top;font-size:14px;color:#6b625a;">
                                        {{ $field['label'] }}
                                    </td>
                                    <td style="padding:10px 12px;border-bottom:1px solid #eee8df;vertical-align:top;font-size:15px;line-height:1.5;color:#2b2622;">
                                        {!! nl2br(e($field['value'])) !!}
                                    </td>
                                </tr>
                            @endforeach
                        </table>

                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:26px;">
                            <tr>
                                <td style="border-radius:8px;background-color:#4c3b8f;">
                                    <a href="{{ $data->adminUrl }}" style="display:inline-block;padding:13px 22px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;">
                                        Открыть обращение в административной панели
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:18px 0 0;font-size:13px;line-height:1.5;color:#8a8078;">
                            Ссылка ведёт на страницу обработки именно этого обращения:
                            <br>
                            <a href="{{ $data->adminUrl }}" style="color:#4c3b8f;word-break:break-all;">{{ $data->adminUrl }}</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px;background-color:#faf8f5;font-size:12px;line-height:1.5;color:#9a9087;">
                        Письмо отправлено автоматически при поступлении нового обращения. Отвечать на него не нужно.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
