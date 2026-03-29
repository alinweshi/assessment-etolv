<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreschoolRequest;
use App\Http\Requests\UpdateschoolRequest;
use App\Models\school;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreschoolRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(school $school)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(school $school)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateschoolRequest $request, school $school)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(school $school)
    {
        //
    }
}
