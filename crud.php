<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait Crud {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $modelObj = new $this->model();
        $data = [];
        $itemSql = $this->model::select($modelObj->getTable().'.*');
        if ($request->search) {
            foreach ($request->search as $field => $val)
            $itemSql->where($field, "LIKE", "%".$val."%");
        }

        if ($request->sort) {
            foreach ($request->sort as $orderOf => $orderBy)
            $itemSql->orderBy($orderOf, $orderBy);
        }

        if ($request->showing) {
            $this->pagination = $request->showing;
        }
        if (method_exists($this, 'indexQuery')) {
            $itemSql = $this->indexQuery($request, $itemSql);
        }

        if ($request->ajax()) {
            if ($request->hasHeader('no-pagination')) {
                $data['items'] = $itemSql->get();
            } else {
                $data['items'] = $itemSql->paginate(10);
            }
            return response(['status'=> true, 'data' => $data]);
        }

        $data['items'] = !empty($this->pagination) ? $itemSql->paginate(intval($this->pagination)) : $itemSql->get();
        
        return view($this->view_index, $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view($this->view_create, $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (property_exists($this->model, 'rules')) {
            $validator = Validator::make($request->all(), $this->model::$rules);
            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['status' => false, 'data'=> $validator->errors()]);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        try {
            $modelCreated = $this->model::create($request->all());
            if ($modelCreated) {
                if ($request->ajax()) {
                    return response()->json(['status' => true, 'data'=> $modelCreated]);
                }
                return redirect()->back()->withSuccess('Data has been created');
            }
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['status' => false, 'data'=> $e->getMessage()]);
            }
            return redirect()->back()->withError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Store  $store
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item  = $this->model::find($id);
        if ($item) {
            $this->data['item'] = $item;
            if (request()->ajax()) {
                return response(['status'=> true, 'data' => $this->data]);
            }
        } else {
            if (request()->ajax()) {
                return response(['status'=> false, 'data' => 'Data not found']);
            } else {
                return redirect()->back()->withError('Data not found');
            } 
        }
        return view($this->view_show, $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Store  $store
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $item  = $this->model::find($id);
        if ($item) {
            $this->data['item'] = $item;
            if (request()->ajax()) {
                return response(['status'=> true, 'data' => $this->data]);
            }
        } else {
            if (request()->ajax()) {
                return response(['status'=> false, 'data' => 'Data not found']);
            } else {
                return redirect()->back()->withError('Data not found');
            } 
        }
        return view($this->view_edit, $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Store  $store
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (property_exists($this->model, 'rules')) {
            $validator = Validator::make($request->all(), $this->model::$rules);
            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['status' => false, 'data'=> $validator->errors()]);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        try {
            $modelCreated = $this->model::where('id', $id)->update($request->except('_method', '_token', 'festival'));
            if ($modelCreated) {
                if ($request->ajax()) {
                    return response()->json(['status' => true, 'data'=> $modelCreated]);
                }
                if (method_exists($this, 'updatedRedirectTo')) {
                    return $this->updatedRedirectTo();
                }
                return redirect()->back()->withSuccess('Data has been Updated');
            }
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['status' => false, 'data'=> $e->getMessage()]);
            }
            return redirect()->back()->withError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Store  $store
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item  = $this->model::find($id);
        if ($item) {
            $item->delete();
            if (request()->ajax()) {
                return response(['status'=> true, 'data' => 'Data is deleted']);
            }
            return redirect()->back()->withSuccess('Data is deleted');
        } else {
            if (request()->ajax()) {
                return response(['status'=> false, 'data' => 'Data not found']);
            } else {
                return redirect()->back()->withError('Data not found');
            } 
        }
    }
}
