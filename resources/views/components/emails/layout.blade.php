@props([
    'heading'   => '',
    'subheader' => null,
    'signature' => null,
])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading ?: config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:Arial, Helvetica, sans-serif; font-size:14px; color:#2d2d2d;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f9; padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0"
                   style="max-width:600px; width:100%; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                <tr>
                    <td style="background-color:#059669; padding:28px 40px; text-align:center;">
                        <p style="margin:0; font-size:24px; font-weight:bold; color:#ffffff; letter-spacing:1px;">BTI-Agenda</p>
                        @if($heading)
                            <p style="margin:12px 0 0; color:#d1fae5; font-size:12px; letter-spacing:2px; text-transform:uppercase;">
                                {{ $heading }}
                            </p>
                        @endif
                    </td>
                </tr>

                @if($subheader)
                    <tr>
                        <td style="background-color:#ecfdf5; border-bottom:2px solid #059669; padding:14px 40px; text-align:center;">
                            {{ $subheader }}
                        </td>
                    </tr>
                @endif

                <tr>
                    <td style="padding:36px 40px;">
                        {{ $slot }}

                        @if($signature)
                            <table cellpadding="0" cellspacing="0" border="0"
                                   style="border-top:1px solid #e5e7eb; padding-top:20px; width:100%; margin-top:32px;">
                                <tr>
                                    {{ $signature }}
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:16px 40px; text-align:center;">
                        <p style="margin:0; font-size:11px; color:#9ca3af;">
                            Ce message a été généré automatiquement par
                            <strong style="color:#059669;">{{ config('app.name') }}</strong>.
                            Merci de ne pas y répondre directement.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
