@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="p-3">
                    <div class="row">
                        <div class="col-2 pl-1 pr-1">
                            <div class="card p-3 h-100">
                                <h4>Location</h4>
                                <form action="#" method="POST" id="import_barcode" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label for="barcode_id">Device Barcode</label>
                                        <input type="password" onchange="scanBarcode()" onfocus="this.value=''"
                                            class="form-control" id="barcode_id" name="barcode" placeholder="Scan Hire">
                                    </div>
                            </div>
                        </div>
                        <div class="col-4 pl-1 pr-1">
                            <div class="card p-3 h-100">
                                <h4>Area Database</h4>
                                <div class="max-h-[70vh] overflow-y-auto">
                                    <table id="example"
                                        class="table table-bordered table-small-font table-hover text-center"
                                        cellspacing="0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Check</th>
                                                <th>Location</th>
                                                <th>Area</th>
                                                <th>Bagian</th>
                                            </tr>
                                        </thead>
                                        <tbody id="table_area"></tbody>
                                    </table>
                                </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-6 pl-1 pr-1">
                            <div class="card p-3 h-100">
                                <h4>Data Device</h4>
                                <button id="delete_data_device" class="btn btn-danger position-absolute top-0 end-0 m-2">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                                <div style="overflow-x:auto;overflow-y:auto;" class="tableFixHeadMd">
                                    <table id="example"
                                        class="table table-bordered table-small-font table-hover text-center"
                                        cellspacing="0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Check</th>
                                                <th>Location</th>
                                                <th>Area</th>
                                                <th>Bagian</th>
                                                <th>Device</th>
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
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCoordinates" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <form id="formCoordinates">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <input type="text" class="form-control" id="coordinates" name="coordinates"
                            placeholder="Input New Coordinates" required>

                        <input type="hidden" id="id_device" name="id_device">

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit" class="btn btn-primary" id="btnSave">
                            Save
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <div id="pageMessages"></div>

    <div id="loading-spinner" style="display:none">
        <div class="loader"></div>
    </div>

    @include('script.iot_pqi.script_manage');
@endsection
