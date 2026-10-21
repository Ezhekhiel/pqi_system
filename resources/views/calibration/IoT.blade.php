@extends('layouts.main')
@section('content')
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            <div class="row card p-2">
                <div class="col-12 pl-1 pr-1">
                    <div class="card p-3 h-100">
                        <h4>Data Device</h4>
                        <div style="overflow-x:auto;overflow-y:auto;" class="tableFixHeadMd">
                            <meta name="csrf-token" content="{{ csrf_token() }}">
                            <div class="overflow-y-auto" style="max-height: 1000px">
                                <table class="table table-bordered table-hover text-center align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Location</th>
                                            <th>Area</th>
                                            <th>Bagian</th>
                                            <th>Device</th>
                                            <th>Proses</th>
                                            <th>Remark</th>
                                            <th>Manual Calibration</th>
                                            <th>Last Update</th>
                                            <th>Calibration</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table_area_device"></tbody>
                                </table>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Container-->
            <div id="pageMessages"></div>

            <div id="loading-spinner" style="display:none">
                <div class="loader"></div>
            </div>
        </div>
        @include('script.Calibration.script_iot')
    @endsection
