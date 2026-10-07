@php
    $mode = ($mode ?? 'center') === 'label' ? 'label' : 'center';
    $file = $file ?? '';
    $src = null;

    if ($file !== '') {
        foreach (['images', 'images/index'] as $folder) {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
                $candidate = $folder.'/'.$file.'.'.$extension;
                if (is_file(public_path($candidate))) {
                    $src = asset($candidate);
                    break 2;
                }
            }
        }
    }
@endphp
<figure class="slot slot-{{ $mode }} {{ $class ?? '' }}{{ $src ? ' has-photo' : '' }}">
    <div class="slot-fill" @unless ($src) aria-hidden="true" @endunless>
        @if ($src)
            <img src="{{ $src }}" alt="{{ $title }}">
        @endif
    </div>
    @unless ($src)
        <figcaption>
            @if ($file !== '')
                <span class="slot-file">{{ $file }}.jpg</span>
            @endif
            <strong>{{ $title }}</strong>
        </figcaption>
    @endunless
</figure>
