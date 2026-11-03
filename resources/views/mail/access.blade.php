<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;color:#32202a;max-width:560px;margin:40px auto;padding:24px">
<h1 style="color:#b62948">Santa Shuffle</h1>
<h2>{{ $eventName }}</h2>
<p>{{ __('mail.'.$kind.'_body') }}</p>
@if($url)<p><a href="{{ $url }}" style="display:inline-block;padding:16px 24px;background:#b62948;color:white;text-decoration:none;border-radius:8px">{{ __('mail.'.($kind === 'organizer' ? 'organizer' : ($kind === 'draw' ? 'draw' : 'join')).'_button') }}</a></p>@endif
<p>{{ __('mail.private') }}</p><p>{{ __('mail.ignore') }}</p></body></html>
