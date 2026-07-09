<div style="font-family: Arial; padding:20px;">

    @if($banner)
        <img src="{{ $banner }}" style="width:100%; max-height:300px;">
    @endif

    <p style="font-size:16px; line-height:1.6;">
        {!! nl2br($body) !!}
    </p>

    <hr>

    <p style="font-size:12px; color:gray;">
        To unsubscribe: <a href="{{ $unsubscribe_url }}">Click here</a>
    </p>

</div>
