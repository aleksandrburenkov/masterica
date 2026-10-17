{{-- TODO(video): replace placeholder with generated Korni loop --}}
<video
    class="hero__video"
    autoplay
    muted
    loop
    playsinline
    preload="auto"
    disablepictureinpicture
    tabindex="-1"
    aria-hidden="true"
>
    <source src="{{ asset('assets/video/korni-studio-loop.mp4') }}" type="video/mp4">
</video>

@push('scripts')
    <script src="{{ asset('js/site/modules/hero-video.js') }}" defer></script>
@endpush
