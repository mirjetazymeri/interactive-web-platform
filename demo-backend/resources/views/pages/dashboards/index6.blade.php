
@extends('layouts.master')

@section('styles')



@endsection

@section('content')

                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Projects</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Projects</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start:: row-1 -->
                    <div class="row">
                        <div class="col-xxl-12">
                            <div class="row">
                                <div class="col-xl-3">
                                    <div class="card custom-card dashboard-main-card primary">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-md bg-primary-transparent svg-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><g><path d="M0,0h24v24H0V0z" fill="none"/></g><g><path d="M14,2H6C4.9,2,4.01,2.9,4.01,4L4,20c0,1.1,0.89,2,1.99,2H18c1.1,0,2-0.9,2-2V8L14,2z M10.94,18L7.4,14.46l1.41-1.41 l2.12,2.12l4.24-4.24l1.41,1.41L10.94,18z M13,9V3.5L18.5,9H13z"/></g></svg>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="d-block text-muted mb-1">Total Projects</span>
                                                    <h5 class="fw-semibold mb-0">454</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3">
                                    <div class="card custom-card dashboard-main-card success">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-md bg-success-transparent svg-success">
                                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="d-block text-muted mb-1">Completed Projects</span>
                                                    <h5 class="fw-semibold mb-0">454</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3">
                                    <div class="card custom-card dashboard-main-card secondary">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-md bg-secondary-transparent svg-secondary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><g><rect fill="none" height="24" width="24"/></g><g><path d="M6,2l0.01,6L10,12l-3.99,4.01L6,22h12v-6l-4-4l4-3.99V2H6z M16,16.5V20H8v-3.5l4-4L16,16.5z"/></g></svg>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="d-block text-muted mb-1">In Progress Projects</span>
                                                    <h5 class="fw-semibold mb-0">454</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3">
                                    <div class="card custom-card dashboard-main-card warning">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-md bg-warning-transparent svg-warning">
                                                        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><g><rect fill="none" height="24" width="24"/></g><g><path d="M12,2C6.48,2,2,6.48,2,12c0,5.52,4.48,10,10,10s10-4.48,10-10C22,6.48,17.52,2,12,2z M7,13.5c-0.83,0-1.5-0.67-1.5-1.5 c0-0.83,0.67-1.5,1.5-1.5s1.5,0.67,1.5,1.5C8.5,12.83,7.83,13.5,7,13.5z M12,13.5c-0.83,0-1.5-0.67-1.5-1.5 c0-0.83,0.67-1.5,1.5-1.5s1.5,0.67,1.5,1.5C13.5,12.83,12.83,13.5,12,13.5z M17,13.5c-0.83,0-1.5-0.67-1.5-1.5 c0-0.83,0.67-1.5,1.5-1.5s1.5,0.67,1.5,1.5C18.5,12.83,17.83,13.5,17,13.5z"/></g></svg>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="d-block text-muted mb-1">Pending Projects</span>
                                                    <h5 class="fw-semibold mb-0">454</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                    <!-- End:: row-1 -->



                    <!-- Start:: row-3 -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Projects Summary
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div>
                                            <input class="form-control form-control-sm" type="text" placeholder="Search Here" aria-label=".form-control-sm example">
                                        </div>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="btn btn-primary btn-sm btn-wave waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false"> Sort By<i class="ri-arrow-down-s-line align-middle ms-1 d-inline-block"></i>
                                            </a>
                                            <ul class="dropdown-menu" role="menu">
                                                <li><a class="dropdown-item" href="javascript:void(0);">New</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);">Popular</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);">Relevant</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th scope="col"><input class="form-check-input" type="checkbox" id="checkboxCheckAll" value="" aria-label="..."></th>
                                                    <th scope="col">Project Name</th>
                                                    <th scope="col">Start Date</th>
                                                    <th scope="col">End Date</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Progress</th>
                                                    <th scope="col">Team</th>
                                                    <th scope="col">Budget</th>
                                                    <th scope="col">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" id="checkbox1" value="" aria-label="...">
                                                    </td>
                                                    <td>
                                                        NovaStream - UI Overhaul
                                                    </td>
                                                    <td>
                                                        25-01-2025
                                                    </td>
                                                    <td>
                                                        17-02-2025
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success-transparent">Completed</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress progress-animate progress-xs w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                                                            </div>
                                                            <div class="ms-2">100%</div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="avatar-list-stacked">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="img">
                                                            </span>
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/5.jpg')}}" alt="img">
                                                            </span>
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/3.jpg')}}" alt="img">
                                                            </span>
                                                            <a class="avatar avatar-xs bg-primary avatar-rounded text-fixed-white" href="javascript:void(0);">
                                                                +2
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        $15,000.00
                                                    </td>
                                                    <td>
                                                        <div class="btn-list">
                                                            <button class="btn btn-sm btn-icon btn-primary-light btn-wave">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-icon btn-danger-light btn-wave">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" id="checkbox2" value="" aria-label="...">
                                                    </td>
                                                    <td>
                                                        TravelSphere - App Features
                                                    </td>
                                                    <td>
                                                        01-12-2024
                                                    </td>
                                                    <td>
                                                        30-03-2025
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-transparent">In Progress</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress progress-animate progress-xs w-100" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" style="width: 60%"></div>
                                                            </div>
                                                            <div class="ms-2">60%</div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="avatar-list-stacked">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/13.jpg')}}" alt="img">
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        $10,000.00
                                                    </td>
                                                    <td>
                                                        <div class="btn-list">
                                                            <button class="btn btn-sm btn-icon btn-primary-light btn-wave">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-icon btn-danger-light btn-wave">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" id="checkbox3" value="" aria-label="...">
                                                    </td>
                                                    <td>
                                                        SoundWave - Algorithm Integration
                                                    </td>
                                                    <td>
                                                        10-11-2024
                                                    </td>
                                                    <td>
                                                        15-02-2025
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success-transparent">Completed</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress progress-animate progress-xs w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 100%"></div>
                                                            </div>
                                                            <div class="ms-2">100%</div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="avatar-list-stacked">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/1.jpg')}}" alt="img">
                                                            </span>
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/15.jpg')}}" alt="img">
                                                            </span>
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/8.jpg')}}" alt="img">
                                                            </span>
                                                            <a class="avatar avatar-xs bg-primary avatar-rounded text-fixed-white" href="javascript:void(0);">
                                                                +1
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        $20,000.00
                                                    </td>
                                                    <td>
                                                        <div class="btn-list">
                                                            <button class="btn btn-sm btn-icon btn-primary-light btn-wave">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-icon btn-danger-light btn-wave">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" id="checkbox4" value="" aria-label="...">
                                                    </td>
                                                    <td>
                                                        RideMaster - Backend Optimization
                                                    </td>
                                                    <td>
                                                        05-10-2024
                                                    </td>
                                                    <td>
                                                        14-02-2025
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-transparent">In Progress</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress progress-animate progress-xs w-100" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">
                                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 80%"></div>
                                                            </div>
                                                            <div class="ms-2">80%</div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="avatar-list-stacked">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/6.jpg')}}" alt="img">
                                                            </span>
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/11.jpg')}}" alt="img">
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        $12,000.00
                                                    </td>
                                                    <td>
                                                        <div class="btn-list">
                                                            <button class="btn btn-sm btn-icon btn-primary-light btn-wave">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-icon btn-danger-light btn-wave">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" id="checkbox5" value="" aria-label="...">
                                                    </td>
                                                    <td>
                                                        Connectify - Tool Integration
                                                    </td>
                                                    <td>
                                                        01-01-2025
                                                    </td>
                                                    <td>
                                                        13-02-2025
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success-transparent">Completed</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress progress-animate progress-xs w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
                                                            </div>
                                                            <div class="ms-2">100%</div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="avatar-list-stacked">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/4.jpg')}}" alt="img">
                                                            </span>
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/14.jpg')}}" alt="img">
                                                            </span>
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/4.jpg')}}" alt="img">
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        $8,500.00
                                                    </td>
                                                    <td>
                                                        <div class="btn-list">
                                                            <button class="btn btn-sm btn-icon btn-primary-light btn-wave">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-icon btn-danger-light btn-wave">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="border-bottom-0">
                                                        <input class="form-check-input" type="checkbox" id="checkbox3" value="" aria-label="...">
                                                    </td>
                                                    <td class="border-bottom-0">
                                                        NovaStream - UI Overhaul
                                                    </td>
                                                    <td class="border-bottom-0">
                                                        25-01-2025
                                                    </td>
                                                    <td class="border-bottom-0">
                                                        17-02-2025
                                                    </td>
                                                    <td class="border-bottom-0">
                                                        <span class="badge bg-success-transparent">Completed</span>
                                                    </td>
                                                    <td class="border-bottom-0">
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress progress-animate progress-xs w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%"></div>
                                                            </div>
                                                            <div class="ms-2">100%</div>
                                                        </div>
                                                    </td>
                                                    <td class="border-bottom-0">
                                                        <div class="avatar-list-stacked">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="img">
                                                            </span>
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="{{asset('build/assets/images/faces/2.jpg')}}" alt="img">
                                                            </span>
                                                            <a class="avatar avatar-xs bg-primary avatar-rounded text-fixed-white" href="javascript:void(0);">
                                                                +2
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td class="border-bottom-0">
                                                        $15,000.00
                                                    </td>
                                                    <td class="border-bottom-0">
                                                        <div class="btn-list">
                                                            <button class="btn btn-sm btn-icon btn-primary-light btn-wave">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-icon btn-danger-light btn-wave">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex align-items-center">
                                        <div> Showing 6 Entries <i class="bi bi-arrow-right ms-2 fw-semibold"></i> </div>
                                        <div class="ms-auto">
                                            <nav aria-label="Page navigation" class="pagination-style-2">
                                                <ul class="pagination mb-0 flex-wrap">
                                                    <li class="page-item disabled">
                                                        <a class="page-link" href="javascript:void(0);">
                                                            Prev
                                                        </a>
                                                    </li>
                                                    <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                                    <li class="page-item active"><a class="page-link" href="javascript:void(0);">2</a></li>
                                                    <li class="page-item">
                                                        <a class="page-link" href="javascript:void(0);">
                                                            <i class="bi bi-three-dots"></i>
                                                        </a>
                                                    </li>
                                                    <li class="page-item"><a class="page-link" href="javascript:void(0);">17</a></li>
                                                    <li class="page-item">
                                                        <a class="page-link text-primary" href="javascript:void(0);">
                                                            next
                                                        </a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End:: row-3 -->

@endsection

@section('scripts')

        <!-- Apex Charts JS -->
        <script src="{{asset('build/assets/libs/apexcharts/apexcharts.min.js')}}"></script>

        <!-- Projects Dashboard -->
        @vite('resources/assets/js/projects-dashboard.js')

@endsection
