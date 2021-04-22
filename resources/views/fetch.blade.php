<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

@if(!empty($error))
    <div class="alert alert-danger" role="alert">
        {{ $error }}
    </div>
@endif
<h3><a href="/tables">Back</a></h3>
<h3><a href="/upload">Upload new file</a></h3>
<h3><a href="/logout">Logout</a></h3>
<table border="1">
    <caption>{{ $table->name }}</caption>
    <tr>
        @foreach($columns as $column)
            <th>{{ $column }}</th>
        @endforeach
    </tr>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $item)
                <th>{{ $item }}</th>
            @endforeach
        </tr>
    @endforeach
</table>
