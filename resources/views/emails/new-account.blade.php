<x-emails.layout heading="Bienvenue sur {{ config('app.name') }}">

    <p style="margin:0 0 16px;">Bonjour {{ $firstname }},</p>

    <p style="margin:0 0 16px; line-height:1.6;">
        Un compte vient d'être créé pour vous sur <strong>{{ config('app.name') }}</strong>.
        Pour finaliser votre inscription, vous devez définir votre mot de passe en cliquant sur le bouton ci-dessous.
    </p>

    <table cellpadding="0" cellspacing="0" border="0" style="margin:28px 0;">
        <tr>
            <td style="border-radius:6px; background-color:#059669;">
                <a href="{{ $url }}"
                   style="display:inline-block; padding:14px 32px; color:#ffffff; font-weight:bold;
                          font-size:14px; text-decoration:none; letter-spacing:0.5px;">
                    Créer mon mot de passe
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px; line-height:1.6; font-size:12px; color:#6b7280;">
        Ce lien expire dans <strong>{{ config('auth.passwords.users.expire') }} minutes</strong>.
    </p>
    <p style="margin:0 0 24px; line-height:1.6; font-size:12px; color:#6b7280;">
        Si vous n'attendiez pas ce mail, vous pouvez l'ignorer en toute sécurité.
    </p>

    <p style="margin:0; line-height:1.6;">Bienvenue dans l'équipe,</p>
    <p style="margin:4px 0 0; font-weight:bold; color:#059669;">L'équipe {{ config('app.name') }}</p>

</x-emails.layout>
