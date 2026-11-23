<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#b62948"><meta name="referrer" content="no-referrer">
@php
    // Only the public invitation may identify an event in a link preview.
    $invite = $page['component'] === 'Join' ? $page['props']['exchange'] : null;
    $description = __($invite ? 'messages.share_invitation' : 'messages.share_site');
@endphp
<meta property="og:site_name" content="Santa Shuffle">
<meta property="og:type" content="website">
<meta property="og:locale" content="{{ ['en' => 'en_US', 'fr' => 'fr_FR', 'es' => 'es_ES', 'de' => 'de_DE'][app()->getLocale()] }}">
<meta property="og:title" content="{{ $invite ? $invite['name'].' · Santa Shuffle' : 'Santa Shuffle' }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $invite ? route('exchanges.show', $invite['uuid']) : route('home') }}">
<meta property="og:image" content="{{ url('/share-card.png') }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ __('messages.share_image') }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
@viteReactRefresh
@vite('resources/js/app.tsx')
<x-inertia::head />
</head>
<body><x-inertia::app /><noscript>{{ __('messages.javascript') }}</noscript></body>
</html>
