
@extends('layouts.master')

@section('styles')

        <!-- dragula css-->
        <link rel="stylesheet" href="{{asset('build/assets/libs/dragula/dragula.min.css')}}">

@endsection

@section('content')

                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Projects Overview</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Apps</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Projects</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Projects Overview</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xxl-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Project Details
                                    </div>
                                    <div class="btn-list">
                                        <a href="{{url('projects-create')}}" class="btn btn-sm btn-primary btn-wave"><i class="ri-add-line align-middle me-1 fw-medium"></i>Create Project</a>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-secondary btn-wave"><i class="ri-share-line align-middle fw-medium me-1"></i>Share</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-4 gap-2 flex-wrap">
                                        <div class="lh-1">
                                            <span class="avatar avatar-lg me-1 bg-primary"><i class="ri-stack-line fs-24 lh-1"></i></span>
                                        </div>
                                        <div>
                                            <h6 class="fw-medium mb-2">
                                                E-commerce Platform
                                            </h6>
                                            <span class="badge bg-success-transparent"> In progress</span>
                                            <span class="text-muted fs-12"><i class="ri-circle-fill text-success mx-2 fs-9"></i>Last Updated 1 Day Ago</span>
                                        </div>
                                        <div class="ms-auto align-self-start">
                                            <div class="dropdown">
                                                <a aria-label="anchor" href="javascript:void(0);" class="btn btn-icon btn-sm btn-primary-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fe fe-more-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="javascript:void(0);"><i class="ri-eye-line align-middle me-1 d-inline-block"></i>View</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);"><i class="ri-edit-line align-middle me-1 d-inline-block"></i>Edit</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);"><i class="ri-delete-bin-line me-1 align-middle d-inline-block"></i>Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fs-15 fw-medium mb-2">Project Description :</div>
                                    <p class="text-muted mb-4">The Customer Feedback Dashboard Development project aims to create a comprehensive dashboard that aggregates and visualizes customer feedback data. This will enable our team to gain actionable insights and improve customer satisfaction.</p>
                                    <div class="d-flex gap-5 mb-4 flex-wrap">
                                        <div class="d-flex align-items-center gap-2 me-3">
                                            <span class="avatar avatar-md avatar-rounded me-1 bg-success"><i class="ri-calendar-event-line fs-18 lh-1 align-middle"></i></span>
                                            <div>
                                                <div class="fw-medium mb-0">
                                                    Start Date
                                                </div>
                                                <span class="fs-12 text-muted">March 1, 2025</span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 me-3">
                                            <span class="avatar avatar-md avatar-rounded me-1 bg-info"><i class="ri-time-line fs-18 lh-1 align-middle"></i></span>
                                            <div>
                                                <div class="fw-medium mb-0">
                                                    End Date
                                                </div>
                                                <span class="fs-12 text-muted">July 15, 2025</span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-md avatar-rounded me-1"><img src="{{asset('build/assets/images/faces/11.jpg')}}" alt=""></span>
                                            <div>
                                                <span class="d-block fs-14 fw-medium">John Smith</span>
                                                <span class="fs-12 text-muted">Project Manager</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <div class="d-flex gap-3 align-items-center">
                                            <span class="fs-12">Assigned To :</span>
                                            <div class="avatar-list-stacked">
                                                <span class="avatar avatar-sm avatar-rounded" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-primary" data-bs-original-title="John">
                                                    <img src="{{asset('build/assets/images/faces/2.jpg')}}" alt="img">
                                                </span>
                                                <span class="avatar avatar-sm avatar-rounded" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-primary" data-bs-original-title="Emily">
                                                    <img src="{{asset('build/assets/images/faces/8.jpg')}}" alt="img">
                                                </span>
                                                <span class="avatar avatar-sm avatar-rounded" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-primary" data-bs-original-title="Liam">
                                                    <img src="{{asset('build/assets/images/faces/5.jpg')}}" alt="img">
                                                </span>
                                                <span class="avatar avatar-sm avatar-rounded" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-primary" data-bs-original-title="Sophia">
                                                    <img src="{{asset('build/assets/images/faces/10.jpg')}}" alt="img">
                                                </span>
                                                <span class="avatar avatar-sm avatar-rounded" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-primary" data-bs-original-title="Charlotte">
                                                    <img src="{{asset('build/assets/images/faces/15.jpg')}}" alt="img">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-3 align-items-center">
                                            <span class="fs-12">Status :</span>
                                            <span class="d-block"><span class="badge bg-info">On going</span></span>
                                        </div>
                                        <div class="d-flex gap-3 align-items-center">
                                            <span class="fs-12">Priority :</span>
                                            <span class="d-block fs-14 fw-medium"><span class="badge bg-secondary">Medium</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card custom-card overflow-hidden">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Project Documents
                                    </div>
                                    <div class="dropdown">
                                        <div class="btn btn-light btn-full btn-sm" data-bs-toggle="dropdown">View All<i class="ti ti-chevron-down ms-1 d-inline-flex"></i>
                                        </div>
                                        <ul class="dropdown-menu" role="menu">
                                            <li><a class="dropdown-item" href="javascript:void(0);">Download</a>
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Import</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Export</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-md avatar-rounded p-2 bg-light lh-1">
                                                        <img src="{{asset('build/assets/images/media/file-manager/1.png')}}" alt="">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <a href="javascript:void(0);"><span class="d-block fw-medium">Project_Proposal.pdf</span></a>
                                                    <span class="d-block text-muted fs-12 fw-normal">1.2MB</span>
                                                </div>
                                                <div class="btn-list">
                                                    <button class="btn btn-sm btn-icon btn-info-light btn-wave"><i class="ri-edit-line"></i></button>
                                                    <button class="btn btn-sm btn-icon btn-danger-light btn-wave"><i class="ri-delete-bin-line"></i></button>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-rounded bg-light lh-1">
                                                        <img src="{{asset('build/assets/images/media/file-manager/3.png')}}" alt="">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <a href="javascript:void(0);"><span class="d-block fw-medium">Design_Mockups.zip</span></a>
                                                    <span class="d-block text-muted fs-12 fw-normal">1.5MB</span>
                                                </div>
                                                <div class="btn-list">
                                                    <button class="btn btn-sm btn-icon btn-info-light btn-wave"><i class="ri-edit-line"></i></button>
                                                    <button class="btn btn-sm btn-icon btn-danger-light btn-wave"><i class="ri-delete-bin-line"></i></button>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-md avatar-rounded p-2 bg-light lh-1">
                                                        <img src="{{asset('build/assets/images/media/file-manager/1.png')}}" alt="">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <a href="javascript:void(0);"><span class="d-block fw-medium">Project_Timeline.xlsx</span></a>
                                                    <span class="d-block text-muted fs-12 fw-normal">256KB</span>
                                                </div>
                                                <div class="btn-list">
                                                    <button class="btn btn-sm btn-icon btn-info-light btn-wave"><i class="ri-edit-line"></i></button>
                                                    <button class="btn btn-sm btn-icon btn-danger-light btn-wave"><i class="ri-delete-bin-line"></i></button>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar avatar-md avatar-rounded p-2 bg-light lh-1">
                                                        <img src="{{asset('build/assets/images/media/file-manager/1.png')}}" alt="">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <a href="javascript:void(0);"><span class="d-block fw-medium">Contract_Agreement.pdf</span></a>
                                                    <span class="d-block text-muted fs-12 fw-normal">1.8MB</span>
                                                </div>
                                                <div class="btn-list">
                                                    <button class="btn btn-sm btn-icon btn-info-light btn-wave"><i class="ri-edit-line"></i></button>
                                                    <button class="btn btn-sm btn-icon btn-danger-light btn-wave"><i class="ri-delete-bin-line"></i></button>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!--End::row-1 -->

@endsection

@section('scripts')

        <!-- Dragula JS -->
        <script src="{{asset('build/assets/libs/dragula/dragula.min.js')}}"></script>

        <!-- Internal Project Overview Dashboard -->
        @vite('resources/assets/js/project-overview.js')

@endsection
