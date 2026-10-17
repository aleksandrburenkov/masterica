@props(['items' => []])

<div class="accordion">
    @foreach ($items as $item)
        <div class="faq-item">
            <button type="button" class="faq-item__q" aria-expanded="false">
                <span>{{ $item['q'] }}</span>
                <span class="marker"><x-ico name="plus" /></span>
            </button>
            <div class="faq-item__a">
                <div>{{ $item['a'] }}</div>
            </div>
        </div>
    @endforeach
</div>
