@extends('layouts.main')
@section('content')
    <style>
        .custom-date-input::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(200%);
            opacity: 1;
            cursor: pointer;
        }

        .custom-date-input {
            color-scheme: dark;
        }
    </style>

    <div class="app-content mt-2">
        <div class="container-fluid">
            <div class="card border-secondary p-4 mb-4 shadow-sm">
                <h5 class="mb-3"><i class="bi bi-file-earmark-arrow-down text-success me-2"></i> Export Data Configuration
                </h5>

                <!-- Global Validation Alert -->
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger text-light mb-3"
                        role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                            <div><strong>Validation Failed!</strong> Please check the form fields and try again.</div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('api.pqi_data.export') }}" method="POST" id="exportForm">
                    @csrf
                    <div class="row g-3">

                        <!-- Start Date -->
                        <div class="col-md-3">
                            <label for="start_date" class="form-label small text-muted">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                                class="form-control border-secondary custom-date-input" value="{{ old('start_date') }}"
                                required>
                        </div>

                        <!-- End Date -->
                        <div class="col-md-3">
                            <label for="end_date" class="form-label small text-muted">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                class="form-control border-secondary custom-date-input" value="{{ old('end_date') }}"
                                required>
                        </div>

                        <!-- Data Type Selection -->
                        <div class="col-md-3">
                            <label for="data_type" class="form-label small text-muted">Data Provide</label>
                            <select name="data_type" id="data_type" class="form-control border-secondary" required>
                                <option value="" selected disabled>Select Data Provide</option>
                                <option value="summary" {{ old('data_type') == 'summary' ? 'selected' : '' }}>Summary
                                </option>
                                <option value="detail" {{ old('data_type') == 'detail' ? 'selected' : '' }}>Detail</option>
                                <option value="maximum" {{ old('data_type') == 'maximum' ? 'selected' : '' }}>Maximum Value
                                </option>
                            </select>
                        </div>

                        <!-- Summary Interval -->
                        <div class="col-md-3" id="div_summary_interval"
                            style="display: {{ old('data_type') == 'summary' ? 'block' : 'none' }};">
                            <label for="summary_interval" class="form-label small text-muted">Summary Interval</label>
                            <select name="summary_interval" id="summary_interval" class="form-control border-secondary">
                                <option value="" selected disabled>Select Interval</option>
                                <option value="minute" {{ old('summary_interval') == 'minute' ? 'selected' : '' }}>By
                                    Minutes (Interval)</option>
                                <option value="hour" {{ old('summary_interval') == 'hour' ? 'selected' : '' }}>By Hour
                                </option>
                            </select>
                        </div>

                        <!-- Submit Button Column -->
                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="submit" id="submitBtn"
                                class="btn btn-success fw-bold px-4 shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-excel-fill fs-5" id="btnIcon"></i>
                                <span id="btnText">Download Excel</span>
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>



    @include('script.iot.script_iiot_export')
@endsection
