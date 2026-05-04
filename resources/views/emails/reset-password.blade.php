<x-emails.layout heading="Réinitialisation du mot de passe">

    <p style="margin:0 0 16px;">Bonjour,</p>

    <p style="margin:0 0 16px; line-height:1.6;">
        Vous recevez ce message car une demande de réinitialisation de mot de passe a été effectuée pour votre compte.
    </p>

    <table cellpadding="0" cellspacing="0" border="0" style="margin:28px 0;">
        <tr>
            <td style="border-radius:6px; background-color:#059669;">
                <a href="{{ $url }}"
                   style="display:inline-block; padding:14px 32px; color:#ffffff; font-weight:bold;
                          font-size:14px; text-decoration:none; letter-spacing:0.5px;">
                    Réinitialiser mon mot de passe
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px; line-height:1.6; font-size:12px; color:#6b7280;">
        Ce lien expire dans <strong>{{ $expireMinutes }} minutes</strong>.
    </p>
    <p style="margin:0 0 24px; line-height:1.6; font-size:12px; color:#6b7280;">
        Si vous n'avez pas demandé de réinitialisation, aucune action n'est requise.
    </p>

    <p style="margin:0; line-height:1.6;">Bien cordialement,</p>
    <p style="margin:4px 0 0; font-weight:bold; color:#059669;">L'équipe {{ config('app.name') }}</p>

</x-emails.layout>
