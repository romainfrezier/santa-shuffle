<!doctype html><html lang="fr"><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;color:#32202a;max-width:560px;margin:40px auto;padding:24px">
<h1 style="color:#b62948">Santa Shuffle</h1>
<h2>{{ $eventName }}</h2>
<p>@if($kind === 'organizer') Votre événement est créé. Ce lien privé vous permet de gérer les inscriptions et de lancer le tirage.
@elseif($kind === 'draw') Le tirage a eu lieu. Votre destinataire vous attend dans votre enveloppe personnelle.
@else Confirmez votre adresse e-mail pour rejoindre le Secret Santa. Gardez ce message : ce même lien vous permettra de découvrir votre destinataire après le tirage.
@endif</p>
<p><a href="{{ $url }}" style="display:inline-block;padding:16px 24px;background:#b62948;color:white;text-decoration:none;border-radius:8px">@if($kind === 'organizer') Gérer mon Secret Santa @elseif($kind === 'draw') Découvrir mon destinataire @else Confirmer ma participation @endif</a></p>
<p>Ce lien est personnel. Ne le partagez pas. Il expire 30 jours après la date des cadeaux.</p>
<p>Vous n’avez pas demandé ce message ? Vous pouvez l’ignorer.</p></body></html>
