@php
    $playIcon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 3.5v17l14.5-8.5L5 3.5z"/></svg>';
    $appleIcon = '<svg width="20" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.4 12.6c0-2.2 1.8-3.3 1.9-3.4-1.1-1.5-2.7-1.7-3.3-1.7-1.4-.1-2.7.8-3.4.8s-1.8-.8-3-.8c-1.5 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.3 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7 2-.1 2.9-2.3c.7-1 1.2-2.1 1.5-3.2-3.6-1.4-3.3-5.4-2.9-5.3zM14.6 6.4c.6-.8 1.1-1.9.9-3-1 .1-2.2.7-2.9 1.5-.6.7-1.2 1.8-.9 2.9 1.1.1 2.2-.5 2.9-1.4z"/></svg>';
    $playClass = ($variant ?? 'light') === 'on-dark' ? 'btn btn-light btn-store' : 'btn btn-primary btn-store';
    $appleClass = 'btn btn-apple btn-store';
@endphp
<div class="stores">
    <a class="{{ $playClass }}" href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer">
        {!! $playIcon !!}
        <span><small>Baixar no</small><strong>Google Play</strong></span>
    </a>
    <a class="{{ $appleClass }}" href="{{ $appStoreUrl }}" target="_blank" rel="noopener noreferrer">
        {!! $appleIcon !!}
        <span><small>Baixar na</small><strong>App Store</strong></span>
    </a>
</div>
