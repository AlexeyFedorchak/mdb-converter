<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<h3><a href="/upload">Upload new file</a></h3>
<h3><a href="/logout">Logout</a></h3>
<h1>Found tables:</h1>

<ul class="list-group">
    @if($tables->count() === 0)
        <div class="alert alert-danger" role="alert">
            No tables found!
        </div>
    @endif
    @foreach($tables as $table)
        <li class="list-group-item"><a href="/fetch?id={{ $table->id }}">{{ $table->name }}</a></li>
    @endforeach
</ul>
