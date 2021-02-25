<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

@if(!empty($error))
    <div class="alert alert-danger" role="alert">
        {{ $error }}
    </div>
@endif
<table border="1">
    <caption>{{ $table->name }}</caption>
    <tr>
        @foreach(explode(',', $rows[0]) as $item)
            <th>{{ $item }}</th>
        @endforeach
    </tr>
    @foreach(array_slice($rows, 1) as $row)
        <tr>
            @foreach(explode(',', $row) as $item)
                <th>{{ $item }}</th>
            @endforeach
        </tr>
    @endforeach
</table>
