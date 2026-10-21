<?php

namespace App\Http\Controllers;

use App\Models\area___db_pqi;
use App\Http\Requests\Storearea___db_pqiRequest;
use App\Http\Requests\Updatearea___db_pqiRequest;
use Illuminate\Http\Request;
use DB;

class AreaDbPqiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function manage_device()
    {
        return view('iot_pqi.manage.device');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function main()
    {
        $get_bagian = db::table('database___bagian as a')->select('a.*', 'b.location', 'c.area')
            ->leftJoin('database___location as b', 'a.id_location', '=', 'b.id')
            ->leftJoin('database___area as c', 'a.id_area', '=', 'c.id')
            ->where('a.bagian','EOL')
            ->orderBy('c.area')
            ->get();
        $arr_bagian = $get_bagian->toArray();
        $get_area_iot = area___db_pqi::orderBy('id_area')->get()->toArray();
        $arr_id_device = [];
        $table_data_device = '';
        foreach ($get_area_iot as $key => $a) {
            if (array_key_exists($a['id_bagian'], $arr_id_device)) {
                $arr_id_device[$a['id_bagian']] .= ', '.$a['id_device'];
            } else {
                $arr_id_device[$a['id_bagian']] = $a['id_device'];
            }
            $key_bagian = array_search($a['id_bagian'], array_column($arr_bagian, 'id'));
            $table_data_device .= '
                <tr>
                    <td><input type="checkbox" value="'.$a['id'].'" class="radioCheckbox" aria-label="area-label" name="id_device"></td>
                    <td>'.$arr_bagian[$key_bagian]->location.'</td>
                    <td>'.$arr_bagian[$key_bagian]->area.'</td>
                    <td>'.$arr_bagian[$key_bagian]->bagian.'</td>
                    <td>'.$a['id_device'].'</td>
                </tr>
            ';
        }
        $table_area = '';
        foreach ($get_bagian as $key => $a) {
            if (array_key_exists($a->id, $arr_id_device)) {
                $color = '';
                $id_device = $arr_id_device[$a->id];
            } else {
                $color = 'table-warning';
                $id_device = '';
            }
            $table_area .= '
                <tr class="'.$color.'">
                    <td><input type="radio" value="'.$a->id.'" class="radioCheckbox" aria-label="area-label" name="id"></td>
                    <td>'.$a->location.'</td>
                    <td>'.$a->area.'</td>
                    <td>'.$a->bagian.'</td>
                </tr>
            ';
        }
        $option_process = '<option>Pilih Proses</option>';
        $table_process = '';
        $no = 0;

        $data = [
            'table_area' => $table_area,
            'table_data_device' => $table_data_device,
        ];

        return json_encode($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function scan_barcode(Request $request)
    {
        $barcode = $request->barcode;
        $id = $request->id;
        $get_bagian = db::table('database___bagian as a')->select('a.*', 'b.location', 'c.area', 'd.id_device')
            ->leftJoin('database___location as b', 'a.id_location', '=', 'b.id')
            ->leftJoin('database___area as c', 'a.id_area', '=', 'c.id')
            ->leftJoin('area_iot___dbs as d', 'a.id', '=', 'd.id_bagian')
            ->where('a.id', $id)
            ->first();
        try {
            // area_iot___db::where('id_device',$barcode)->update(['id_device'=>'']);
            area___db_pqi::insert([
                'id_location' => $get_bagian->id_location,
                'id_area' => $get_bagian->id_area,
                'id_bagian' => $get_bagian->id,
                'id_device' => $barcode,
            ]);
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

    /**
     * Display the specified resource.
     */
    public function show(area___db_pqi $area___db_pqi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(area___db_pqi $area___db_pqi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Updatearea___db_pqiRequest $request, area___db_pqi $area___db_pqi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete_device(Request $request)
    {
        $id = $request->id;
        try {
            foreach ($id as $key => $a) {
                area___db_pqi::where('id', $a)->delete();
            }
        } catch (\Exception $e) {
            $data = [
                'alert' => 'Failed!',
                'text' => $e->getMessage(),
                'color' => 'danger',
            ];

            return json_encode($data);
        }
        $data = [
            'alert' => 'Delete Success!',
            'text' => 'Save Database Success!',
            'color' => 'success',
        ];

        return json_encode($data);
    }
}
