@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="row">
                <div class="col-12 px-1">

                    <div class="card h-100">

                        <div class="card-header">
                            <h5 class="card-title mb-0">Area Database</h5>
                        </div>

                        <div class="card-body">

                            <div class="overflow-y-auto" style="max-height: 1000px">
                                <table class="table table-bordered table-hover text-center align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th rowspan="2">Device</th>
                                            <th rowspan="2">Building</th>
                                            <th rowspan="2">Area</th>
                                            <th rowspan="2">Location</th>
                                            <th colspan="2">Manual Calibration</th>
                                            <th colspan="2">Last Update</th>
                                            <th colspan="2">Calibration</th>
                                        </tr>
                                        <tr>
                                            <th>Temperature</th>
                                            <th>Humidity</th>
                                            <th>Temperature</th>
                                            <th>Humidity</th>
                                            <th>Temperature</th>
                                            <th>Humidity</th>
                                        </tr>
                                    </thead>

                                    <tbody id="table_area"></tbody>
                                </table>
                            </div>

                        </div>

                    </div>
                </div>

            </div><!-- row -->
        </div>
    </div>
    <div id="pageMessages"></div>

    <div id="loading-spinner" style="display:none">
        <div class="loader"></div>
    </div>


    @include('script.Calibration.script_th')
@endsection
