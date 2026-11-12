@extends('layouts.master')

@section('styles')
    @include('partials.datatables-css')
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Employees
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="responsiveDataTable" class="table table-bordered text-nowrap w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>First name</th>
                                    <th>Last name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Hired Date</th>
                                    <th>Salary</th>
                                    <th>Email</th>
                                    <th>Actions</th> {{-- Optional --}}
                                </tr>
                            </thead>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('partials.datatables-js')

    <script>
        $(document).ready(function() {
            $('#responsiveDataTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: '{{ route('admin.employees.index') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'first_name',
                        name: 'first_name'
                    },
                    {
                        data: 'last_name',
                        name: 'last_name'
                    },
                    {
                        data: 'position',
                        name: 'position'
                    },
                    {
                        data: 'department',
                        name: 'department'
                    },
                    {
                        data: 'hired_at',
                        name: 'hired_at'
                    },
                    {
                        data: 'salary',
                        name: 'salary'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });
    </script>
@endsection
