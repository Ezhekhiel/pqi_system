<?php

namespace App\Http\Controllers;

use App\Models\database___area;
use App\Models\database___bagian;
use App\Models\database___location;
use DB;
use Illuminate\Http\Request;

class DatabaseAreaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('andon.manage.area');
    }

    public function main()
    {
        $get_location = database___location::get();
        $get_area = db::table('database___area as a')->select('a.*', 'b.location')
            ->leftJoin('database___location as b', 'a.id_location', '=', 'b.id')->get();
        $get_bagian = db::table('database___bagian as a')->select('a.*', 'b.location', 'c.area')
            ->leftJoin('database___location as b', 'a.id_location', '=', 'b.id')
            ->leftJoin('database___area as c', 'a.id_area', '=', 'c.id')
            ->get();
        $option_location = '<option>Select Location</option>';
        $option_area = '<option>Select Area</option>';
        $table_location = $table_area = $table_bagian = '';
        $i = 0;
        foreach ($get_location as $key => $a) {
            $i++;
            $option_location .= '<option value="'.$a->id.'">'.$a->location.'</option>';
            $table_location .= '
                <tr>
                    <td>'.$i.'</td>
                    <td>'.$a->location.'</td>
                    <td><button type="button" class="btn btn-danger" onclick="deleteData('.$a->id.',\'location\')"><i class="bi bi-trash3"></i></button></td>
                </tr>
            ';
        }
        $i = 0;
        foreach ($get_area as $key => $a) {
            $i++;
            $option_area .= '<option value="'.$a->id.'">'.$a->area.'</option>';
            $table_area .= '
                <tr>
                    <td>'.$i.'</td>
                    <td>'.$a->location.'</td>
                    <td>'.$a->id.'</td>
                    <td>'.$a->area.'</td>
                    <td><button type="button" class="btn btn-danger" onclick="deleteData('.$a->id.',\'area\')"><i class="bi bi-trash3"></i></button></td>
                </tr>
            ';
        }
        $i = 0;
        foreach ($get_bagian as $key => $a) {
            $i++;
            $table_bagian .= '
                <tr>
                    <td>'.$i.'</td>
                    <td>'.$a->location.'</td>
                    <td>'.$a->area.'</td>
                    <td>'.$a->bagian.'</td>
                    <td><button type="button" class="btn btn-danger" onclick="deleteData('.$a->id.',\'bagian\')"><i class="bi bi-trash3"></i></button></td>
                </tr>
            ';
        }

        $data = [
            'option_location' => $option_location,
            'table_location' => $table_location,
            'option_area' => $option_area,
            'table_area' => $table_area,
            'table_bagian' => $table_bagian,
        ];

        return json_encode($data);
    }

    public function save_location(Request $request)
    {
        $location = $request->location;
        try {
            database___location::create(['location' => $location]);
        } catch (\Exception $e) {
            $data = [
                'alert' => 'Failed!',
                'text' => $e->getMessage(),
                'color' => 'danger',
            ];

            return json_encode($data);
        }
        $data = [
            'alert' => 'Success!',
            'text' => 'Save Database Success!',
            'color' => 'success',
        ];

        return json_encode($data);
    }

    public function save_area(Request $request)
    {
        $location = $request->location;
        $area = $request->area;
        try {
            database___area::create(['id_location' => $location, 'area' => $area]);
        } catch (\Exception $e) {
            $data = [
                'alert' => 'Failed!',
                'text' => $e->getMessage(),
                'color' => 'danger',
            ];

            return json_encode($data);
        }
        $data = [
            'alert' => 'Success!',
            'text' => 'Save Database Success!',
            'color' => 'success',
        ];

        return json_encode($data);
    }

    public function save_bagian(Request $request)
    {
        $location = $request->location;
        $area = $request->area;
        $bagian = $request->bagian;
        try {
            database___bagian::create(['id_location' => $location, 'id_area' => $area, 'bagian' => $bagian]);
        } catch (\Exception $e) {
            $data = [
                'alert' => 'Failed!',
                'text' => $e->getMessage(),
                'color' => 'danger',
            ];

            return json_encode($data);
        }
        $data = [
            'alert' => 'Success!',
            'text' => 'Save Database Success!',
            'color' => 'success',
        ];

        return json_encode($data);
    }

    public function deleteID(Request $request)
    {
        $id = $request->id;
        $option = $request->option;
        try {
            db::table('database___'.$option)->where('id', $id)->delete();
        } catch (\Exception $e) {
            $data = [
                'alert' => 'Failed!',
                'text' => $e->getMessage(),
                'color' => 'danger',
            ];

            return json_encode($data);
        }
        $data = [
            'alert' => 'Success!',
            'text' => 'Delete Database Success!',
            'color' => 'success',
        ];

        return json_encode($data);
    }

    public function location_change(Request $request)
    {
        $location = $request->location;
        $get_area = database___area::where('id_location', $location)->get();
        $area = '';
        foreach ($get_area as $key => $a) {
            $area .= '<option value="'.$a->id.'">'.$a->area.'</option>';
        }
        $data = ['area' => $area];

        return json_encode($data);
    }
}
