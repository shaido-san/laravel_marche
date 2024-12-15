<div>
    @if(empty($filename))
    <img src="{{ asset('images/no.jpg')}}">
    @else
    <img src="{{ asset('storage/shops/' . $filename)}}">
    @endif
</div>

