@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@foreach(['location', 'type', 'active', 'booking'] as $key)
  @foreach($errors->get($key) as $message)
    <div class="alert alert-danger">{{ $message }}</div>
  @endforeach
@endforeach
