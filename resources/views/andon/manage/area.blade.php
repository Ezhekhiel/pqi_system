@extends('layouts.main')
@section('content')
    <div class="container-fluid text-xs">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="p-3">
                    <div class="row">
                        <div class="col-2 pl-1 pr-1">
                            <div class="card p-3 h-100">
                                <h4>Location</h4>
                                <form action="#" method="POST" id="import_location" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group mb-4">
                                        <label for="location_id">Location</label>
                                        <input type="text" class="form-control" id="location_id" name="location"
                                            aria-describedby="emailHelp">
                                    </div>
                                    <button type="submit" class="btn btn-primary pt-2">Save</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-3 pl-1 pr-1">
                            <div class="card p-3 h-100">
                                <h4>Location Database</h4>
                                <div class="overflow-y-auto" style="max-height: 400px">
                                    <table id="example"
                                        class="table table-bordered table-small-font table-hover text-center"
                                        cellspacing="0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>No</th>
                                                <th>Location</th>
                                                <th>Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody id="table_location"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-2 pl-1 pr-1">
                            <div class="card p-3 h-100">
                                <h4>Area</h4>
                                <form action="#" method="POST" id="import_area" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group mb-4">
                                        <label for="test">Location</label>
                                        <select class="form-control select_location" name="location"></select>
                                    </div>
                                    <div class="form-group mb-4">
                                        <label for="test">Area</label>
                                        <input type="text" class="form-control" id="area_id" name="area"
                                            placeholder="Input area">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-5 pl-1 pr-1">
                            <div class="card p-3 h-100">
                                <h4>Area Database</h4>
                                <div class="m-2">
                                    <div class="overflow-y-auto" style="max-height: 400px">
                                        <table class="table table-striped text-sm text-center">
                                            <thead class="thead-dark ">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Location</th>
                                                    <th>ID Area</th>
                                                    <th>Area</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody id="table_area"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-3 p-1">
                            <div class="card p-3 h-100">
                                <h4>Bagian</h4>
                                <form action="#" method="POST" id="import_bagian" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group mb-4">
                                        <label for="test">Location</label>
                                        <select class="form-control select_location" id="id_location_bagian"
                                            name="location"></select>
                                    </div>
                                    <div class="form-group mb-4">
                                        <label for="test">Area</label>
                                        <select class="form-control select_area" id="id_area_bagian"
                                            name="area"></select>
                                    </div>
                                    <div class="form-group mb-4">
                                        <label for="area_id">Bagian</label>
                                        <input type="text" class="form-control" id="bagian_id" name="bagian"
                                            placeholder="Input Bagian">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-9 p-1">
                            <div class="card p-3 h-100">
                                <h4>Bagian Database</h4>
                                <div class="overflow-y-auto" style="max-height: 300px">
                                    <table class="table table-bordered table-hover text-center align-middle">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>No</th>
                                                <th>Location</th>
                                                <th>Area</th>
                                                <th>Bagian</th>
                                                <th>Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody id="table_bagian"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="pageMessages"></div>

    <div id="loading-spinner" style="display:none">
        <div class="loader"></div>
    </div>

    @include('script.pqi.manage.script_manage_area')
@endsection
